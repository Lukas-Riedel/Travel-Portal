<?php

    use Service\Service\Device\DeviceType;

    class CreateDeviceHandler extends Handler {
        public function handle($input, $roles) {
            global $deviceService;

            $device = $deviceService->registerOrUpdateDevice(DeviceType::fromName($input["type"]), $input["token"], $roles);
            return $this->createResponse(201, $device);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Devices";
        }

        public function getPath() {
            return "/devices";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a device";
        }
        
        public function getLongDescription() {
            return "Creates a device.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Device", '{"type":"Portal","token":"devjFpQfdQ1WP6cG0X6DrY:APA91bG-rysyc75dRrt1acBH11y41g3HDiMuK2HsEOzDbI5Mh1vGBn-1Da6TggFUQb28KlIWDHRFBmmhFv7XHDvWTZFihX6bOCDcUQCzIFxa9vFGKKcVJsc"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created device", 201, '{"type":"Portal","token":"devjFpQfdQ1WP6cG0X6DrY:APA91bG-rysyc75dRrt1acBH11y41g3HDiMuK2HsEOzDbI5Mh1vGBn-1Da6TggFUQb28KlIWDHRFBmmhFv7XHDvWTZFihX6bOCDcUQCzIFxa9vFGKKcVJsc"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>