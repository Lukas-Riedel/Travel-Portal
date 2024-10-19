<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class ListPlaceAlbumPhotosHandler extends Handler {
        public function handle($input) {
            global $processorProvider;            

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
            if ($response["code"] != 200) {
                return $response;
            }            

            $album = $response["body"]->findAlbum($input["albumId"]);
            if ($album == NULL) {
                return $this->create404Response("place_albums", $input["albumId"]);
            }

            $response = $processorProvider->run("GetMediaItems", $input);
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Place Album Photos";
        }

        public function getPath() {
            return "/places/{placeId}/albums/{albumId}/photos";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 5295),
                $this->createPathParameter("albumId", "integer", 227));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "list_place_album_photos";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of photos for an album with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of photos for an album with the specified identifier for the specified place.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Album photos", 200, '[{"id":59601,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdMkrpD0UOiwx4GApQdiGm0p2mXRDFEOh3OJdRrbFB0WjwrZD2wPAvNFEtzWfcaEAKjrpow4vwR_Oi-W8eSbY2LWUEWGtYHHzfBf8cIWuBqhyjsZS8_WMJ5XDYER56V1QmZQSM7ZQRq-uEUS6Lq3ZMEQ6JLfDcLaxA7IicJaTf2WpBp8BQf-4Ty0cFsu6XpDzQ54lGYIIUV6oulAlk7bPacv43ihSP5FilghK2LJ8k3W5G145_E5oGyrXFLNwR795sPn-Xp7fBFW76Cn3kGl4AAXhAuMLm3owezYnmj3TmelzizR2qEx55OjjyNNLFXhBShr1XheUh5C7m2J0kad4rZbfEfy0FuAegle8Sez8PoL9Icy9RnE6z6dkJn41eJlDydPDrZCZGUNMUFP-domWNU7L6iyleG1spnPfEguBSqGi7ZFrAWVqrL3O-01g_Sszx6AzKS2XgDxQ0LqrfTdMHp_re9hkNUHBGMEr5x5qGtgUcgQJXzw0YsLBzA4fywOwvOA_ap77_xwGqhCOvN3BYQAEwf0GtugUHWHfePm4UMFtAkQo5U7y6Cgt8ALXyyPL61ipwzYGHRVKJ7JfmcRkxLW8oXRYJacEhwain0QMlYKlco1rWE-umelk0Bxt5dK5G8yqGIdTxzHowvwVCGCexKQeMbdO--SWgvnER5JacMhDYrNOTGarPJX9MtQ2bsR7x4KIblmAAQpp7gI53g5wl52tCQJQvw9mx4GOUIAhPL9zQFv5YugLte4vMG3Q17oSWDDibU3NtyquITWT9hJ_d5u6Hb4QTRDmSFfg5qucwdnNo_1R6j59oOrBHkc3F0tDftHccbXwgRS6zH5eP-RGCzosqgWSwg3NyyZyjs7QUFmvH1OgCbbSFZ7ddNV6_MFtETn_OSdWZnv7PlGxnIUak_W6UiZ8vh_EJNsSdyvJPOQDwB9F98-CTS3sd_Jy0yY6JA-PhwjQvhor5jHlT8D_Za5khXQ1ImMAm4cmv9SrOs529ttnF6VdYWrAW-joUp9A","focalLength":18,"aperture":9,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708540024},{"id":59602,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdbzrEaAFcZ41dUr_IbRzuxANx9MkUFk6PNrdLIe2OAl5b_41alByB2lFt68SjJSDxHbgub-XmGznJ0nRxzdfNUCzD1j3b1SepCLPajXL2wCO8v-WtlMJ2lZnnMiruNog6Nbrq0_wyTy4jPzrJjHOuFVkH620dyfcd-NAObMOCeYWB9SuaznX6FjSUytnhflJno7rk2KdxY50l7tlmLea_G8mlDkutfoz2QH9_ym4kIEblmJqpA-EFPVyyzS5Bv8q6fTU1TO95FLCEYl9M6LfnZyastHYiG-JlYiKSAcWXPCDJEYiq2v1wBA8vO3LcyLFJp496KwmrK3SYe-Czps23erMpC_cesSey_Vw-mN3NnfrvTSdKgW7bHnX1bw6kfJGUAin8mP9urZyhoFLzBGgAShAo_iIZ9ZABluJW4nElZNPmL_MYVkwV_i4APuZZRaCqxBtHfJ6ncxXS-NmxjCyG-UY69JqwxyK7wG2pjab1UN_PumF17oBHa_gPzPmB2Hf5XvnyQoofykww8pVJPaMdc7MB8RGax_IiJa4yx0WaKEq2klnp7aYXc7LvnNII9am1YMcUS8c-67g1YNYPWa6WLcY7wwBULFqwwXtVQp5iNdiamK38FqcmZ1Ds_tpZiA5-qO6DV9BLvlIvnkRVqhOKnCT_l-1xdfqkwF-9wJXNKKyvKu2U0Ccomq95gmDoTMuuAEE6LxTdbWaFEQ1V1Z6zb39iBX-1GmYECArX5spCkCWmx0ot9MlVo2b7GV4DZhi2WBdWkAmGeA8HAQ3JHJU72kystRO1bIKH_JGTxWPM_yhYbTSZe1GSfDfwB0udj-SJ7zaS8XIp4T6oV9L1ysT16C3-KzKMTFASdLNg7XZQpGB3mBzWs3rZCTlQfkGk5wct1YMw2y19kwIMfq6SG5ADT8qfLgtW1fztOuKnA61jO27d1ApYMangqOjG9loiqD04OynQa-3Z5RmtmDijIs_QzwU3xNdPwvK2kriCHIm6gWXIGJyBtcJQAxS_8AsitXA","focalLength":18,"aperture":9,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708540066},{"id":59603,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKfhes47vFHPgU74Jl_htcviFGOPDOKPFtexAzUP4mskWOfWV09D9V2Wnt9f1gYgmePTZddsdOCECdglM9uLhCbsKrcfYBlXuT5jGu-Oc2B9igeT2gO36VCz7g0vN0EB1BAJ_oQ6Dr4svvrfK5ZNc8cwQzwZrBerTZ8Hp9AZcm2tKRUZEdV3Lr89LZADIJIOVxYAWPQ6On3fZj9xq411On7OuKw0Nj1JOwt6Yt-tg9iF-uiIDQzL9kWdJB-8FGtUt4JnBuAoRnKjvqKrvt4pzEPcklMQgU-MMmDWJBZMlr-38ONy1YIjQ6wf68awH6ANENL9HFyl6kWwXTiRfESVNYZrBu8sKJHN28oFyTQefh2DpLBVS4hRJzIdN4UxZWBZpWnYzO52m7CuFaonIz89umCvAbolgwExuox-3An7hFVgWd_qarAWSk8kVErBLXWogzZ814ZfYUpZCpliAcagSXNrclR1qliXTEM6U9drRieinWYapHx0D7T--1hXQy3pUUo7b6SDLew7SLtjqidSBoRUMoSDcEc_w79vpd4wXMxhgoe3r4FMcbvo93Xb8lgATH6ZAsbA8Wj-Z48Oq-Lu8PJj0mDEvABAXW2FpzXagsQC5Dgh2HWB2P9ipl3QfDyD4BQnCjLKQPQX6rTE8DCH4Zl68AlSXoGt7PjaA6Ozuw93xMCTuewkKa3Tmli55S6ru3EeUj-SmI3xpox2qwzGdKRIVOeWBnM--4Bers_4y8w5NVJSUOES_GmGxo8k2shbZHqRU5oWUiGapxalw6ptfQG_aPClDGRMqRJpIe4aapEGUWazmVgoLukMyCxP47glIVElRBCAl-OnaCVjrIs3FNwDBa7Itimufz3WQ23TPfT-65NxK1sYJVViVPlEacjCqOkyzN11lla2ffomeqvgz_93DHJN5FW6y9jKrFKsdslekkj2kjTNMtD9FnW4xdjskdjPLEWjIn8Wxq7JzzSvsbUghv0icAhWKmWV0ee_zZSs-cj0udnxQrQgWxdO6Y5_lA","focalLength":18,"aperture":10,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708541717},{"id":59604,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdXAaIO1nGJdsJTMk8UjkGbCjM04553pubSd4P4dKk3Qb8X7q78A4E_49d1DgZgTHrZ-ZgNBJJiiFuWlsLTzjWfIkrvJHipIUrUFGTfBRUKZx5XpMQgewB0boYl4h2AosSpXmK98kPKS6Nwfc3P8HMs4l4e9SF-GjVPUVPSKULhFAsnpHCGaG8WXqZ2ALxqXobqaFT4tsI9m-M7Zxipv5BSHOwIZFecraAa2cS21yJ5QNVP-HktzyavPb9rIJgkGwM9Ou9pM0jZ3TTSyW37e2eB-6zN1X1WfRfkZmP2vsividVcHfTDpSbpCnVDiS1E2ehWOXJg6HgUDBkxDaPI9Ur2jmURfOfxzafAokdKEpcxLuFQPzvOxkwMTmHg15-jV9fGN0o7Po2YtQfCHvOHMEG06VNmMhLo8UGcDU52gMn2c0REJY9Rnx6I7QI__q9iOQALqCoVYauAC7hjKFik8ODjicMRqSJWt9JxaEGBeqNLaVVoJOHngZjwecJJ21x829jFF1DMe1EPkkMu1gCkB4kEGO6UW6QcMdDAHfqEAfkQ1bdRH3tB4eAF2_4v7AslPh1pUleOh_IEyKuf--b96dWHC24amV7ItgDrNGK9kwtg-UmH7ktpaTbV4mCHUGIU7DfxnabLguvzf9i9B_AYzmlatb1ax4ZQiBDPb2Sf_yObpScPl1fwNf11Gc1mqD6Yop_oTGUCrniFrs3LkDVJlweFE1ZIqYMOaw5-nArdvIXnWwNBxtoGJRuzJGgXAEmWRA_NtJJcJ4M6SouDDHNPJe69LTkX3hgBD7IrjpwDvrN2olf3niYKZfXbyeNpiJMjyEdrG93v8lNWk1QIiY8tZM5MAjWPLZzvRDSpTvLRtZFqmg3OEsPoSKQyzKTXfwTBG99kgce_oRUkzm9y8ah4RuJJsCxBq4MJAqDVmB9yHA7s8l1w9CJoTti5Cx2-NrSwYhdCTB-0yMkP2FlBvdx1YnDV1dHisW9f8ckZAg8v5Tc5pLO8KMfqKrb3RTjFsDz31A","focalLength":18,"aperture":10,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708541722},{"id":59605,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKcI4izBbanXEoZW5e2sXGf6zPB0sP9yfpuCVPKdOwhdW-pnFHQaGQxCqO-ViFv2G9z5TkMFVtdoYbERLKNxCc7QZ6ykJkkhDcPfklaTJHK2h2mSo_hgIyhh2eEIghaIXWxnPEtcQ3ZFAZuwKJ8OqilmvVTvFfOzZmsXw-q2qRQEmO629GlQXdBuV2sXakNb9ett2oOei_ohm5thSBQObpDtPsuB-ggEeFNg-JU4Wnt3BalehwjW63O-N0w2aWPy8QFqWUtdjTXSrGJGbaiN8oZfAoN6udGC2Sb4m_e7lUhz3FOavTtK-W7u0_dIwMLBjusvgPgIbE4MMSNw5ECgTUhWrIFh6SgX4hQtmQD8QxCly0sUmFqEUT1TdJosD3wuuSsFCmYoX2DT5YIFkRLITTGM5_uZhaMcnJN6oaf-MRXv5p0IW803geg67jRzHlNV_7ASSg01CJ-BRezjqrNNas26LkkJUh9uweoZL_6o1CDBueIHEYHx-VuzK5fn9-XK-8iEosJHIyYvIltpuzfHsRKC7XRuiZd3aKsXJXXsvtSsuvsp8KlPsqJNGj1tdJvSpCqrj2KQ9xgKE97dtvO0tf0FFTcUz6hHSxJ1rJSfYytOOrbZSFbom-YXA1O9A2ntjnUhQX6WYFiBPegjEQqHadJ_egWJcSeXsP39qTMFkEEwGKY72G0vt8mSIoke-2bKXXIKnWVJmTnzWFPvelBxVmQ742WxUq1AEuv5weXNqTIdq7wTeBv8p3_bMlVuq0gSIY-hefg-dW9iRiidJwaRD4Y7qudZ3jXtTAeqm9MDb1T1ViYLwctzC8fwAslXT0Y3I3vM9S1VruLa23FzKhGIf74tUuIv0ACoAAUr-lzbhWBPxZFM8XWcAu6DH87HEVLXq9ZomJONOeYlGFa1gScYNR8rWNj97vi1jQuvsi6UzKHpE3DGLsUEd4Uqa_j6eXSEHoYa01F1iwUdsTD-jtcqxE2sJqkapWyeFDaHeMzUJXtRVl-rrb_mYkiPkl58zcBRPA","focalLength":18,"aperture":6.3,"shutterSpeed":0.0125,"iso":100,"timestamp":1708543054},{"id":59606,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKe_Y91tbHR27s3VC3XjizbeGUUsnw8-axwkXCjddAdCIDx22SBn-TL1EKXmXG5hMqIwXw9a46G52LyEDnc84xrvKbqWTfVVXE4tN_RingJtW_ouNtx9qAht0oY-XqYJQ305nplnPBqKYcTz_cIfcYmkuHUJhrC2JhurjSHFuPX-5Jmq7T6wGSoSNRE_WCDo90uNjg1d3IFTNZRYuExFgOBJrJgqKKDDCA54e6mqhKEHi5Dx3DJt9GrHZs8cQDUPIQVHyBb07P-QvZjIWszzsIpcmN1XJtLC7IjUBSZvpmURPAMLHgSbk-XwFsiWDgpxKHcgqq6XiB7LZK4KZhe9u3GBnthTCOWJr-IaPK48_s9r9Qt3WBIqvsjl6ywl5NUk5QWt7mGglPIPIxqbfGtIOipRIlT02p5TDA4s-QgS0CORixxQeszl7u62BXi8UYW__Q3Jpf6Rw6EDOV7lsof2u4kIkGZAM6M5BC0uDwDz62l-VWE5v55JUZjG65rPX-WmVvf0BUWrfIFWfVNI7YQDaiqnnZMPUxCQG0yTBsZ_k0iz9C_9G-jkv52MejKJpm3wa97dkL9I_juAIoPzHcd58ZBHyFIfDFIfFXQJeHq2pVhyd6CJhp7oI-8Odbt__tA0Z406Rfv6znPftbq4WmCEg5qj0GdhwoNYtkQ4NtE-hJ_UXdidkHkZ_fKbG4wm_5XyzO6iQ2540Crx-pYQV49qH9uQ40eSD1uINnSLFF8WnnaO3MsOa6T0B5N4o7GBQVvrij_c_Fmrb_D7NrgAwz5Da5QcgRsxDMHL74niR2W5EInnKm-qC_0BH3wS5UiJJNv9prT5cNWfZamLL3cTMS7HpGa5hHg8Bh_pzZcGReds_2PwVPtjZDwuZzk6MQ_86eGOfAZJG2fICBbYX24GlvujKVKmf250XiALqU-blHRAZvyQfYeW9PUeG7SSW8h8gRY_OA5ho192BXRC23IL6hWy5DxbE-RpEJpmlbP3XOMANVw3e2NIKq8qGKkx0SnUJXR_1Q","focalLength":18,"aperture":5.6,"shutterSpeed":0.004999999,"iso":100,"timestamp":1708543074},{"id":59607,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdBWVKWxEIESvsPXnE7rsb2C0P4WQQpIGvhy7hOwHCP8rR8TaQz8holw_yuruf_S6iDY4CdCxtJJXtzVOmgbTN190x8idzG2hVjt4221KNpB_yoEI2F3Lv2fJFKhHdbWqfdahomIB-HEjDfICvIIj39QjbeADEoSoPpi2cyerOPdn3iitRRxYPTPd9tUA8XEQam1NTljXLjRf16kGRGMLkM59VF_SkMaAGVAKwu55RuiBzA_8541JC1R2foWdUWHUN7y4yRA3wd5oEcuKQidC9e_sJqk9lAmL3PVZiKooKzQupLMnoLAURLlQhU2rCV7N1DyvxhtIZXmHYcNKtjKyO1jc2Nk_xSZcAtcK7R1TpVpLc1vknlASCSTV2PhHyrPCwG_jJ7CLQIgYa4jdQU5HEHLENqoJFA_SgzAAkQax6fv92GdagKvd2I0zUM9CrXSw8wqAQpSiwDuwmL2IRNT_JNSWqoJu654SGnojsqupyj33u_N2crzhADVugg9_Itk52Guja_mxPrUtufJ4O-nInEREDwcHqo05ptJFSoFRlI3016wkjURy4iQL833C03UGDIdSvMdPjanB4CRHQOjj6ODeszPqnyVNT9JgrA9zYTRuVPlki0D4VXncV1UXn2B7L_k9Iy1bIBlttIikZCfm9P5y5WhbP_WyChJvLzoxsp7S2OmWj8UQUoNTz3fIxyApUjkNsaJwfrz8Spptl0bCdH2slMWhnlIEM1aWufGoS14ZSAtjmS1rqlXKz0Q86ob0EoXJEcGfulXD4mcSd47H9IcqHq6pebbb6mqlC9XyeODVhCs4VsBBZ254EYI3EvdSfAED-wyJajZFuGonMH6OoxqyaXOIXHO0K1uyXH-qb6aYGnqMsbNrbYUD4w2LC1USmWd2GjxclR2w-va5-MYbJLm-PNf2mnDhngDHE_hmF1pGfPEwkpqIf-8_www64uLAnC8f8T7wMXMwnZyOuCGBN32EqfD2K4TvsGzDCe72sVC1KL8NY1-KYUYmEPQ7t5XQ","focalLength":18,"aperture":5.6,"shutterSpeed":0.004999999,"iso":100,"timestamp":1708543080},{"id":59608,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdN7VR_DYt9edwL7vL3TTr9lNe7vPzIeBgJ1pogmz7mk2RMQ3p5SoDlDi8kR7wWjYE6I8HdLecD3iDE4Qi5N0gj8OEE65xLzovBQZDyESwLY2d84yPgUXBBFn61ZoDWYEpPKXDx-qawaD0X6KZF2B32v__z5_hs6lZ7pMwKm7mWW2iU6izjJsw9OmW-hUNjAszkI2KUwp15WWpTJDPvZ9Xfje6kinfOhhbqBJdhS5sNWUAZ7749LBT9TFLtapjoQQ5Mk8ZHeVyAjOq002YdfGg2250CE8b2NNUgO1hqngrDZciqRmOooUhoEsJndRvbtbJ3nksMEZKjQubB8VCYxIexixtYj5cHHN8-DJ0rUQejt5x5wah4Cpgl4SspTShvlasRl3q2D54GOMhcdopWCX0FVzLOEnSrZS6t899V7-DrOHUjyA-rfK8VSr0R8xKOy_cPeJBTgBqqZcLA52N-65HaZiRRxPeOGMFBxhj0z9gbNt7Q3XFQSxGgrRsoS8aXgrdcW__Bd7BjRA3KS3R_OOC7b3ynARaal5svE7bUdeszCHqptVi8CTu4WygH8wIRBn2D90t_mkoQG2nmsGEJAccnFpnvAtyMtT0sf_Zpnnn1s7PUU89LZc4gv06BId1PnYEu556ePuXobUaBcniMwjJ7WXzkln15bg08lw2mgD956wmkoOL6jxYKxKypnYjUNn561pDv1iGT57WaKii_E9tkmT_WD1oGQ6p-WzEW3rd-__VC6nC2DdddO2dk2Y0M44Iv0aN1zxsSo46xBHl8NgFBbsnjFUGe0H1hOhw38v22BFBfDa3PD2Zx1PtkCT-tB38cJUpZSmxqZZ_NPXYvDd3_ezIbT-IHemsBZulRXcSRz8sqi_aft87VX8NtD_hfGmkHJRD3MPPEjCXAnUuhfCgyX4ZdtJUPIUyIv7jBM2YAjTcsmqX9y4-g_knn_VmdwufPGGXIixBbevy_wOokqqsowY0ERiDCJDXryhZq-0Gg-IfkxuOohOvXFpMzGfDcsg","focalLength":18,"aperture":6.3,"shutterSpeed":0.0125,"iso":100,"timestamp":1708543107}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>