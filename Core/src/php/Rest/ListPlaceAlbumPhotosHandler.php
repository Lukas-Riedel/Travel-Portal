<?php
    require_once(__DIR__ . "/GetPlaceHandler.php");

    class ListPlaceAlbumPhotosHandler extends Handler {
        public function handle($input) {
            global $photoService;            

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

            $response = $photoService->getPhotos($input["albumId"]);
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
                $this->createPathParameter("albumId", "integer", 2203));
        }

        public function getMethod() {
            return "GET";
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
                $this->createResponseExample("Album photos", 200, '[{"id":129987,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKf2T7n3LdRucrjT-yQgqfv_o96vcOLy5Zs8sJow2zwvUOhoj_ujP9xBFNT1r-Z0SxQRfd12hvaUKOrrdszSHwJ-gx4rmTi1vyaeIEpIMTOFLxvItLCJBgx60wzaT3zs1xl3aAORPoU3fE41SryWUluZ4MIjDDUN3FUrVafn1qtwF8tpuZ-IKR7VjUgUOP7x-3O-Zm_7vsS4FAxlx32m3EEWSStSqXPkcMl9MtsBXzOC3hSckDbIzhu-2NBW4MILNmfTUTuMOSJGKuiIkRJdJr8FHtgDJhR8FIOB3J2tqvXJOqPssZ_x6AbXcFwEl-zg7xG-Lj29JODrcQW2-bH5_RlcACXWxR3_CKjSarwrhiV6cYmplgE6im3q3YOS5KpmDqxHdxXvwi0vDLR3zjnSgf4u0sWOMr5X2WP7yxudpjD4RRmvBytSczgej4D-0Ug9se65MXYMzA6ZJXjEFy8trX0E5siSiqc-rwd72CKyw0BZhSQdvVPaNrE2xNEqJ07PV-YTEFtSxCkpYhVqa5tIU_mQu24ERsfNvlzD2rBYr6bb9dvTmu5g-1lMcjYjPBO1tgGj5mtT6POrfowwb_8RKO46PSlBLDID9tuqNzPQ9FHH1AWLOpVAw00Cpipzl7xN0k2yI3yA-TQXaT3trP4Lkj_qeDFXhEmz8TpEwcyj-8f2Ix7Fy98yFSXRwpzsl0hzDiBFU27ja40oJgXkoo44x8E-dW2s1S_C9ypOjEi2TOGJ_eYSFkBdfvEPY0WC7WOvfZ_spUdtUM_NoF-lqmwT2yXOcXNdUlbtYA8-CeAVSMmd6Q3w3-49yfc2UeysLjh4ZpnB7dDOXPJ_izLG6WghBzsBoWZtl4VVn_qia0vjGvaeEC_O__cwy77YhhgLiTkzzdBQDjonC9Q7WIWtpR_qLUnPllG_1qt8CHspAsrtIPnqDokNOkqfsZcKIXD1XubIzoj4uwjAOAegPpKo1RlmnA5vs3aLEMf-z-pKTegEoYv7aF-ushESccG4NwI","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswm-BZXKwAifE3JYSadq8aARaZtkHXhr51mZBgrvT1IwozwAgSG8h-viZAnzSlw_fYSzU-APFfbZ8UYCMdK0C091hI64lA","focalLength":18,"aperture":9,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708540024},{"id":129988,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKccty-Y6JFdzar4krFt04EsCK2rSDymh06cPglVl-xyg3lnVlmcdkcuI9GlcXCHEbjJqyXPIOEySbfCz0QBQpDYJf4DQHAKKgt2BeR64GksQ3V9dxoK2ksFvu1kZhfLT96VW11-1dU-zdgtKXM_AMdyDHE3sZyUsuwxZxbzXb5Yl2vOIqOKCZULcAIfM8hHXCdVpRCJUB8-xaV2fbzVTgUYbnkW0ZXgvNC7x6xy7wlHJAwWaeaAA4-rMKHq3paosIp2VNBon9RrR-WP0Ak_Grpwwm6t226792HMEHwxy9X5JClCIBGi655K4J-_6XBKhWn8rE20I0oCuu6VnXOoNnHrIQuQ2_yfelSdSFrAO917gsBAs9H2Aa0GR4VqVx7R7mX1t7cViDdx23m0jqvpbhGc19gRuxKZ3dD0ggVdIRnBL0jf31jH_jMmZxf8r1Z4qtKimOvCOIfJ5J7-P-PaXTM0dq7kzzgs6lN1DVvTwCaYZwlWsjVUjbRijNRYql-fXSoC9-wG0j0x6Pn1ZBa9OkXLJtI3NIDfMRo507BCETnXuzzkjktMrzTbAVvz22dARZhMsB8YxCZjWKgfh6rP2DIRXZo9rnqjN5XdSYeR1MOPjNRjSY5lmu5WeslXiXyrXS1ZI-Bh0pEGIc5KcPjs65A54vxqUljZOIlERT8XpCa5xsZI1TEtoBXj_gQ5EjriLkn3CPfxIpxQHPY8P5FEyRhX5kH8Dh0Nypx3f_8NUn0bLn6mk-XNseQvzjtdA0z1YuaKGFlBLod37sKZt0zCdM_IbW_xxAIDGge9N8CcJqROoZyvX3t2Biql___Fecbw_WsieWFPsLCLkyW9UMfuDHpUvuvIos__Zu2JB44KvI-dgWdLzUZ3VBv1dU4yibdXsvf077sRl1CkqYeT8KloC3SDDTSr-ggY-vdX4RaTHkHHSmhu063HwXhL01LPNcrkPRkqqhJj37UFKfCm92mVK8gHu2hCRIXW9NB65ijrCm3iWE-1xCgPpWd2uw0","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswlmydBZ_kZQ1MxANAnRz4YAZkIgC8Iuugr9riyurYCp8KeqhPWcg4Z_nJs567sKHjZXMndGeDvLhE_nV3ONdB0U6M9CNg","focalLength":18,"aperture":9,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708540066},{"id":129989,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdy811jvnhXZt3rj0Nis-QXuO9SNbATgl7ApJVwNiWvLWEYDsGZGzTwZ956KZVTuUQDJT3ZUQjBL1_BjLItsM_P4ukdJXbrrU7HeH-NQi_7va5qrLpJzyV6E8x8Z23h4EsYPPXj74plCgdetkkX0idyUlSQm3eUKiFfywy3cqHp8oeaxvduN2aabR3BzE5K1Nn_p0Jn0qxBlkKhGLBN12TDxsMwYTxKw0AgBo0gkzdh6IcPtaNd9tb6gbBDZ5zZt6SX9rJNBwi65HKpPheREVMMvT69lDWU_-4yS7pH-3bgGvRvcHRiEI3Ma1NOSctklP7nxFyJkJmRj8UjbvvExg8XBSSbSPgItFkVdmn2n99TdXYnw2nhDPluNkCYzFMA_bkZxF_ZyaH0xLKwoNm3JafPL2EugfM4Z-cYOcHTX25mA3q9eB177CSm1gmLYZdsho23iZ8zSI_pv78aLDagNU8MaIIeuuE9N6NPFgPXw-5SYrdR6qqlRtKzuPQSF2Kg1rhz5wwAPFvXD2IRHTr5Pxxqbd9XN2WcSVY66dBs9asTbBHvesNX92HwIHgoj9DMDX8U3BrRb6TpR7Bp16njKxlnm0rS9WcZBV4N3uUV7-5JoKLgRc2SHzEEY68XPUw6rWxA8loNa27RIFnvd5DLxkzXRTs7yTsje4GIwxiqI0I9TT8DCiKibTAciHhLvMFPOXNUghzoaJ102JH_A0TABwU44Yn2qkTKdEWG2NNbrMAc5qTul_RQSkfFU991Rl75VSsXAdV0O4IlnYeHWU--RUxS3iUzdrbCBGsY-ephIMrPgc_SVRbDZ_cEBip-_N6gJl914r8TFSIrULrjWBCHH_da99LnRfX1NcZBrRZQZfdZarcs3Pvp7n7SATe-gHd7xxrgdmfx2C_0ftsAsPiKsUXBVYR5YUTMtIMZKArqtgXvtkaVe_LuWlDoVizDn-YlP6MA9m6Hnj1v-sDEtm7mGLXQTvGa-IssGvwNGgy33ZOfrhVyMzBtnaMWVRM","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswk2OdeL86T6VmRd57oPm0fbxDT1UKuHvjKrWAlwHf5xU5mJH5gbEuHoD7pLappFI27gyupg0zjEmybtulk9tJw66wi-ww","focalLength":18,"aperture":10,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708541717},{"id":129990,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKfnZyR01zwwreHJqaEcNLiiOn9GQ7zc_osuli5EtcSKA66ewqZ58XQjvpJ222YrVLFjrM11K7WBkW5KRk6XbNKhqPPlEWl9-4b3s_VcfKLPACkmW_AuGX6ViEnXiwDdEd0AKZ2eS2ntnQl94jasqUF0YO8-bBLi_Ba20otDHt-t9nIJUNzmUn0kqj1JqVOqE9SgWfG5gYdIJluBGSZH4T_3jCBWWRb0Ziy-fl-lfMitIEXjznLgkWhPeK9jTxRSBIEPKGQWEM--U7nvjb8nJSDW81eEIocIAk8abXkqx0BBUdMsBSlKhG6Fv2hS_qCfh8ZXE-yzVAwC3jk2-RRzBnxcCkKOT7KcgcyzaDpi26Dc1EAZvDHrhYjQIMamvYt93OJNvA4EfdMuax_GWOcff3-6Xa2JlV8JbhaiQ4McmB39JI8PsDDy4cvqdag1EtfuXrU4KLtMpsj7Bva5tKKXnbosP3P2IipShlsHf3NJ6OTGg1BkMcHi66Zsp97cYZ0mT7qcOiKaDIgjyta-Wx7aDNlWJgcAgd-GIWqDK7RT_uyBGt4JgH47KEnrAzVtPRdnTqC9aC_FiYQRs2-zPO0ur-E7MP9b3edwzMgeuvu5LJTmSGt7_jkxspd0hPPC_drTEAv4BuDuwChB6tEB6vimPt9pb9ky56ob_EHGXMnkqyeURVP9RQiL66tpQ1AgRJDkVnIthTLQb19Yrm4SsmbC8fbbN1RZ1p0DpbXB1cdrrhDE00xBg6cZA0vIR3XIOus7LthOTyrlt11hSWtV6lLFO13ASOOYYcgXwKPaE-krKuAi29HUWv9KN5Ii92lBJzUVGZGeSpy0mMUvg4HwgYRN9t8lOwbcRFqkKgXFKcbzPkZdcduNHTG9ZJ8OvcJgH3Kc61dKMXXysQQJiCHhxfg1EMtks3WRPfvGtpFyK7AxZ0OWIoKv1ZjCLyd6rPJnSNHbVJMrKQrbHq4VyE3n-cMlq4WISootfmpNuv5b830nVf4yXuAKtq1WXnTsIPQ","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswm6FSmiQvia6xs9KViY-4OtOIPar0213KxE2fazsg66igivsds8XGsjTA52Xo2QIPHHaufrHVWQ1RUNYy9b5KMaoBuo3w","focalLength":18,"aperture":10,"shutterSpeed":0.009999999,"iso":100,"timestamp":1708541722},{"id":129991,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKenv_G48LITiORovuMC9TS2Udh9QWbqmAQZR83fpfSh28VvhqCEnDGV6sl9_8Tjl-0Emaw4Hv4pela7euS9hVvleK5CfpWNAqmuylCZ7E_aHv3tEuYQ0Z-eXCwvgOToPgKXcTAfBvIiJCxKOoQRUFZaSUYh2h1XyushHKuvTg121QSyPYgiyvNq-2by58l7RSx9Bma_mmsyYcCAxXdbSTuMFp-Ujp29WYukenFsHZfADWFEu0IRGMWUdGDd-0aAoFL0f4hNTBv9lhbKYfOs-ePZAPPJE76WlRbHA7rvCU7q6izax-CubMkal1zufG6ttpOSm_XmHtcGQ6cD0XduhfHoxyITlOyEWPGziVDxbyHgmRsrK63R1oIr998fA0R3ogeTrnVgDwAiW0itjOlWSEU3eYxbyS0QUGdWLFuTedhDsD8HaVwR7FVd5GYlEiGZ5opWu4OXq2TMeWVd4GEeXx-Bw6PT2bg8SWId6T_Yf2WXR1vbaVQbTHyh_8POJktYbQT4g5Kk4VqUwA3QlmRfBxFJcV8rfcLv9VD-MenKClPT3YG99am5klEBU6aAl8YLUXNkCAfJw3-GMP-uZbbmeTKDBc152hQfRZTriuTqwLT3--fNmymd7U0i--QvMnw2gr74lPZP3SAzb928hoV4bJzmL0ujGzOI8BnvCTioHcouN1_PXceLJ-qhYNnkLr5NHx4gyS9NFwIUCCa8p3mKx48rWB2FXvxix25Tbt-sJjk6D5EX2ysqjYw-08jCVrMTkPMPMLf3MQT3ci07zWeouomzarhdvRaXWjpzPZtEwOka4fhzFvM6DQuv9KhIP8SrCSQFCaNTAfvNn8ieZX86VpN4Rks2WBlZzaKcMSEbrgBpcI1NGTeYlUfDHo0jSpd2n3uDVbnNZTLGg2Xra5Amlpi18-noLwCXjngEExyFjL3qzaJcPQefgARA150TzrelHH5sBQz6vOeVZhUuLxuYCQ8iA4kB15d347IkCoE0FkiJujrpYcSDCTIX4dI","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswkjHK4t2LPUwXDV86516GhgaU-FHAVNGKIhAZe3v0DilQJBdsSKjXjsEPKIFr7sFwX0YkWTEF6QZyIgcvD0nRJup8I9cg","focalLength":18,"aperture":6.3,"shutterSpeed":0.0125,"iso":100,"timestamp":1708543054},{"id":129992,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdLOLKdVsqZ6rbyppd36e71llK8Pbquyokh6RC7tNqej5JhlmgRHuXysxvyQgnHr-av7tpzelGbxdBJZaCO66sMPu1bQO7QpzEsAdhSNwh0IjIQy7JVEt-CcjXz7TF4W13PxaY-24H1AAKuLZVno70enLyaCPHpSFI5kJ2UcxKMpQ_ajjqjTxFswWWWFeEACee85L_8CPk4sb7xbuzMJ1zbyTrfbuugElgaE5opWZRGKHj3aA3jPCqPeK80AzmlG8C9mnLSB5O_XZLxUV67SevMzRugUrdkqverfca0ebMbW17BAnCk4RhqGvA_nkEmMw3ZnbLPEzwOnxzkmNr9I8UxJTSziLWd7RwYUmsWIhJnNWnFUN7BHsd-i1uRI_LQt_0Mk-lbbWz8HTVWezA4917zRFFb1QOu5j1gpN4dTewK489eRhvymQffB9ApQZvrkEUQ-72aAcuH0HICf0yejoxJqae65suidIQIOY8-kJFypAw4jK5aDY0yUf_xb0fZ3JpaYX935T2OyBm8rsH07LT0uGWJSGXG7zF2c_Z2usHCJ1hA7VLiDxHgG0-mEVzpgdr1oJMaKLlAMRuvSSg_bAZqdzCRXG6cbAtMLrn6N2QfFaBrXSzbKRk3SrAE4GcZ7D5fVhCk7HGU2TpP-oxUQ41MRZXwJ1CQXGTp911J_PVrLJq7kdNPVesDK0VtJwTKi3f8u8tu63P16F9AUeUltfs3WKY3DpgLAnphtmp9c4HNtlUREg1pFZGDSfhNZBcGPBU76iKrWuYQFB_H5oKDOsH7_zVoTDjgHUmVxOT1oTnNm8VEUcNGZC1mf8-7NYWVesDbmDvevwPXSqx7gdS_3OWt8PmjS47ZzspEat6iwGITt-huU1hnsdD6vrHvDLkZ7PZjZtQyP8Qij6pyuGvB2OVHExXg61wDWPlsldo5vKpmt_byxOICj_VieyhWNSMwSq3F7yN6Z2WptwLD7Pe3zRs2RyDTiIA8TV9u_jswDcyYTqFRzY-uMOSE9fo","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswngaVoJUO3FMmLupVIQe95rWoA3qorEob-W35ppT0QWv_G-qxB6Wvt1xFPsynuqevALDqbkcOSGRJQONZ8glRtEXzaPyw","focalLength":18,"aperture":5.6,"shutterSpeed":0.004999999,"iso":100,"timestamp":1708543074},{"id":129993,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKdzDnh7kYvw_DAfgqHlZo9i-VhBxm5I5iXKAEpDvXuF8xHW8djmG-3epfVwYyPUDAQJOO6CcV1N7aot14693VmYbeRUAMuRODaVHo6nwtGKHrB4oG9fpqIERsWMANyh_Ozr24fKmrcieWOpWzAj0Zbe3hdrtDwaqXn0L_7ZEWPhoHhADf0XGC844UtiCTrH2SPYEtR0ZIHJvlu1EvB10QNlJOej-gUOh7x8LEyW_YyZDSsf1EHmttquckea0KzCuQLt2OrORRI6dz0H84XDIYz6CaLFijnYxDtsEvMPXjT2xLNlkG-q5LqQOmWh7R9u6_gkGywsB-t77ABS1InDRAK7pumdMqeOHvqj1se_IIwKHAppUDnpkLo22cJ8VGVe3Ytird3l6drkS3C9wzAmJRKwR_lm514NWgPePXg5_NkKEuldIUMAWl-QUCivG6s1ravq2ppWzABulWrdsOTc-TdXdHYNYxtbHsUKJsN7Gn7RxTQ5Iayzcptork0dOT-ExsTbO9UOAjQOtu2AGDpkPW0nEZ3RaTdvefcb1LCw_NQEiapPlBS2Wf94OYrG2y_a_foUdRM-GT6gRj6wDoDtXQTTRhXfYSRnljNXUV7QjAPmNjyPHWRKyfH7Jq1b9atyPTLGzI54vBo-uXBftqTQ8-pqu4M3tsUgIc83iAyDd98Y_coxkHJ-DPbf6hBhVqhAU121k6vFQa3TrPDO8wh78QubmGzUCxTlLwnnCP8zZj38rwm6kp4kOmmj5NaNLbh-Q2hqUEF4VKgrQ8li2nBSyku7pnfPDUxbAGRop7RwGKgN7oVMqTRzckPazNgwcS0AYOBEKhVka_YG7EMqYtviUBi9gyOIYLA9URIK4rt-v2xUbUp7wqpYvjd-NvIqfFSB1_mFZLslFRE-rDF_Ji_zcviusnBD-_3FJy4hUmAn9vqCTGVCYifYACry_8RtvwaiCWW4TrcMkxfZADUQFxa5nCJNfQCdpYelhxd6aflYxI0OIpPpgSeBKksbuuo","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswm9X8MaNWb0W8GWPjrBaN0q5FoUQxj_kpAqmVvYc7HCxRsi2WIhqbmHz1tS2cdSOanqHA3buOjy-pTUF8h6Hka4FipOvw","focalLength":18,"aperture":5.6,"shutterSpeed":0.004999999,"iso":100,"timestamp":1708543080},{"id":129994,"url":"https://lh3.googleusercontent.com/lr/AAJ1LKfqPzN-OJ_D0p-uQoZuJHQIlijOVVtvjB-MaHnTxeUkV874QtBZVEjFanyzaJiIJqtqyIKoHWKI1rLDKGAe8KinbU3VmByElouqVmv6kXtdVwCWggMNRObTKEotoCwtqCwm7f4rKgexOcAdi0H6BSxoc-CxHCJX10_l8r0mReEj4Swd1fIgjiB3XgsZQb--JZgnw1FfzzdYXM_zvEDbzPYWA4hMLhPlofYqzkfFQ6al6u_37FaDUlKcD12EAvbFo4AZH_SW7dqFiD5on_e1stuxmZ697nNEDyXiKNDsNeS4WRPxE4VfFelHfvR2aTsqGaqxWvN8f4_XzTXZbPYQt105akjUW0582eJIAWRMkv2QqMUSG0AxTtBaAq7R5rXHNeqlrW7K4rqX3W5kuSHz6wvx1hFNmUC30Fz5raiWR8y7Kwtk6hxQIkh6ZCaIBhbETw0dMVm8LrZ97JG7xEbxk6PwkXGtJv01VJe8yEfAhLoaBLwwduR3trXh34kd2mMCabwDI2_BGRUKQw8Ki5p-5WI9IzF3kb845jU4rqWArYUYtFcdZJvpugIZM2yuOkmqoJFKNgB5qOtffxzehyfyIgcHkhbtEGb6j5lbi3PBxTX847RhfS4Z691BruOzKmr92-fs3Z9OaAVsZnnYVsWro7JctDcrj6DWjrZj2r07xE22zl7zgrfxrUMLyOlkZVw65P1EX22_xWF-tdY834rrkued0Zfi6KMb0ZsX-ezixM3Xu5VqLSEETxoX9mkPZdMXiqKbP5lsJ5lCcKia6QPB9EOV0XIL7cqSHGe-BKSp0x-l1FnMmghhNMdwlSqcdAKgDwVFMnJxcfGhFGjIIgqpGjSUhOL9rFrpKQCTbC_irENWedqHLGZRoVhBa3O-GJpAfGmhjWCWApY8d1lwIdRTZhy4gZfwt9CanpZtt8C0oQktoeRGPNYTe5U6J5_StT7GAsGkBz3avKxhiu3D9wqn5I2ZnWnZ55UhIkLyuGCh8Il-JqAej_aM3XnK6DU","permalink":"https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswnhgTLANFdOeUDuJmNsenBvgoXQy_Uveh5pFxmWzrAvcuaSXpNV_PHNEyVtpl-dtSFmxkPuT7wWIbiilClhM0C3liPSow","focalLength":18,"aperture":6.3,"shutterSpeed":0.0125,"iso":100,"timestamp":1708543107}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>