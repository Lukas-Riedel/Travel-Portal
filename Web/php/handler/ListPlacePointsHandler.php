<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class ListPlacePointsHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $processorProvider->run("GetSuggestedMapPoints", $input);
            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Place Points";
        }

        public function getPath() {
            return "/places/{placeId}/points";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "list_place_points";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of points for the specified place";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of points for the specified place. This action calls an external AI service to obtain a list of potentially interesting places (POIs) within the place.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Place points", 200, '[{"name":"Eiffelova věž","latitude":48.8584,"longitude":2.2945,"description":"Eiffelova věž, postavená v letech 1887-1889, je jedním z nejznámějších symbolů Paříže a celé Francie. Nabízí panoramatické výhledy na celé město a je oblíbeným místem turistů. Věž je zvláště krásná v noci, kdy je osvětlena tisíci světly.","color":"#FF5733"},{"name":"Louvre","latitude":48.8606,"longitude":2.3376,"description":"Louvre je jedním z největších a nejznámějších muzeí na světě. Nachází se zde široká škála uměleckých děl, včetně Mony Lisy a Sochy svobody. Budova sama o sobě je historickým palácem a architektonickým klenotem.","color":"#33FF57"},{"name":"Katedrála Notre-Dame","latitude":48.853,"longitude":2.3499,"description":"Katedrála Notre-Dame je nádhernou gotickou katedrálou, která se nachází na ostrově Île de la Cité. Stavba katedrály začala v roce 1163 a trvala přes dvě století. V letech 2019 zde vypukl požár, který způsobil značné škody, ale obnova probíhá.","color":"#5733FF"},{"name":"Sacré-Cœur","latitude":48.8867,"longitude":2.3431,"description":"Bazilika Sacré-Cœur se nachází na kopci Montmartre a nabízí nádherné výhledy na Paříž. Je známá svým bílým exteriérem a krásným interiérem. Stavba baziliky byla dokončena v roce 1914 a je významným náboženským místem.","color":"#FFC300"},{"name":"Champs-Élysées","latitude":48.8698,"longitude":2.307,"description":"Champs-Élysées je nejprestižnější ulicí v Paříži, plná luxusních obchodů, kaváren a divadel. Táhne se od Place de la Concorde až po Vítězný oblouk. Je to také místo konání různých slavností a oslav, včetně vojenských přehlídek.","color":"#33C1FF"},{"name":"Vítězný oblouk","latitude":48.8738,"longitude":2.295,"description":"Vítězný oblouk byl postaven na památku těch, kteří bojovali a padli za Francii během revolučních a napoleonských válek. Slouží i jako památník neznámého vojína. Nachází se na Place Charles de Gaulle a je jedním z nejdůležitějších symbolů Paříže.","color":"#FF33A1"},{"name":"Moulin Rouge","latitude":48.8841,"longitude":2.3324,"description":"Moulin Rouge je slavný kabaret známý po celém světě, který se nachází v čtvrti Pigalle. Byl otevřen v roce 1889 a je známý svými extravagantními show a ikonickým červeným mlýnem na střeše. Kabaret se stal inspirací pro mnoho filmů a muzikálů.","color":"#9B59B6"},{"name":"Musée d\'Orsay","latitude":48.8599,"longitude":2.3266,"description":"Musée d\'Orsay se nachází v bývalé nádražní budově a je domovem velké sbírky impresionistických a postimpresionistických uměleckých děl. Muzeum bylo otevřeno v roce 1986 a nabízí bohatý výhled na pařížskou kulturu a historii.","color":"#16A085"},{"name":"Palác Versailles","latitude":48.8049,"longitude":2.1204,"description":"Palác Versailles, nacházející se na západě Paříže, je synonymem barokního luxusu a královské grandióznosti. Původně byl královským loveckým zámečkem, ale za vlády Ludvíka XIV. byl přeměněn na monumentální palác. Proslavil se svými nádhernými zahradami a Velkým zrcadlovým sálem.","color":"#FFD700"},{"name":"Pantheon","latitude":48.8462,"longitude":2.3456,"description":"Pařížský Pantheon je monumentální stavba v latinské čtvrti, která sloužila jako kostel a nyní je místem posledního odpočinku významných francouzských osobností. Stavba započala v roce 1758. Je zde pohřben například Victor Hugo nebo Marie Curie.","color":"#E74C3C"},{"name":"Sainte-Chapelle","latitude":48.8554,"longitude":2.345,"description":"Sainte-Chapelle je gotická kaple z 13. století, známá svými nádhernými vitrážemi, které pokrývají celou její výšku. Kaple byla postavena Ludvíkem IX. jako místo uchovávání cenných relikvií, včetně trnové koruny. Tato stavba je považována za jedno z vrcholných děl gotické architektury.","color":"#2980B9"},{"name":"Centre Pompidou","latitude":48.8606,"longitude":2.3522,"description":"Centre Pompidou je moderní umělecké muzeum a kulturní centrum, známé svou inovativní architekturou. Otevřeno bylo v roce 1977 a obsahuje sbírky moderního a současného umění. Budova samotná je unikátním příkladem high-tech architektury.","color":"#8E44AD"},{"name":"Place de la Concorde","latitude":48.8656,"longitude":2.3212,"description":"Place de la Concorde je jedno z největších náměstí v Paříži, známé svou historií a krásou. Nachází se zde obelisk z Luxoru, který je hlavním bodem náměstí. Místo hrálo významnou roli během Francouzské revoluce, kde zde byla umístěna gilotina.","color":"#27AE60"},{"name":"Luxemburské zahrady","latitude":48.8462,"longitude":2.3372,"description":"Luxemburské zahrady jsou nádherné veřejné zahrady o rozloze 23 hektarů, které obklopují palác Lucembursko. Nabízejí krásné fontány, sochy a dobře udržované záhony, což z nich činí ideální místo k odpočinku a relaxaci. Zahrady byly založeny v roce 1612 Marií Medicejskou.","color":"#F39C12"},{"name":"Opéra Garnier","latitude":48.8704,"longitude":2.332,"description":"Opéra Garnier, známá také jako Pařížská opera, je monumentální neoklasicistní budova z 19. století. Je to jedno z nejznámějších operních domů na světě a nabízí širokou škálu operních a baletních představení. Budova je také známá svým elegantním interiérem a nádherným vestibulem.","color":"#D35400"},{"name":"Metropolitní muzeum d\'Orsay","latitude":48.86,"longitude":2.3266,"description":"Metropolitní muzeum d\'Orsay, známé jako Musée d\'Orsay, je muzeum plné impresionistických, postimpresionistických a art nouveau děl. Muzeum sídlí v bývalém železničním nádraží Gare d\'Orsay a poskytuje návštěvníkům fascinující pohled na vývoj moderního umění.","color":"#C70039"},{"name":"Musée Rodin","latitude":48.8556,"longitude":2.3156,"description":"Musée Rodin je muzeum věnované dílu francouzského sochaře Augusta Rodina. Nachází se v Hôtel Biron a přilehlých zahradách. Mezi nejznámější exponáty patří Rodinova socha Myslitel. Muzeum poskytuje jedinečný vhled do života a tvorby jednoho z nejvýznamnějších sochařů 19. století.","color":"#581845"},{"name":"Père Lachaise","latitude":48.8614,"longitude":2.3934,"description":"Hřbitov Père Lachaise je největší a nejslavnější hřbitov v Paříži. Je zde pohřbena řada významných osobností, včetně Jima Morrisona, Oscara Wilda a Edith Piaf. Hřbitov je nejen místem odpočinku, ale také krásnou zahradou plnou uměleckých náhrobků a soch.","color":"#176BB6"},{"name":"La Défense","latitude":48.8924,"longitude":2.236,"description":"La Défense je moderní obchodní čtvrť nacházející se západně od centra Paříže. Je známá svými vysokými mrakodrapy a moderní architekturou. Dominantou čtvrti je Grande Arche, moderní triumfální oblouk, který poskytuje výhledy na celé město.","color":"#E67E22"},{"name":"Invalidovna","latitude":48.8566,"longitude":2.3126,"description":"Invalidovna je komplex budov, který původně sloužil jako domov pro válečné veterány. Nachází se zde také hrobka Napoleona Bonaparta. Stavba byla dokončena v roce 1708 a dnes slouží jako muzeum vojenské historie Francie.","color":"#82E0AA"},{"name":"Conciergerie","latitude":48.8562,"longitude":2.3445,"description":"Conciergerie je historická budova na ostrově Île de la Cité, která původně sloužila jako královský palác a později jako vězení během Francouzské revoluce. Zde byla vězněna například Marie Antoinetta. Dnes je součástí komplexu spravovaného Centre des monuments nationaux.","color":"#5DADE2"}]'),
                $this->create400ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>