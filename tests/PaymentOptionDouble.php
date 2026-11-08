<?php

namespace PrestaShop\PrestaShop\Core\Payment
{
    class PaymentOption
    {
        private $moduleName;
        private $callToActionText;
        private $action;
        private $additionalInformation;

        public function setModuleName($name)
        {
            $this->moduleName = $name;

            return $this;
        }

        public function setCallToActionText($text)
        {
            $this->callToActionText = $text;

            return $this;
        }

        public function setAction($action)
        {
            $this->action = $action;

            return $this;
        }

        public function setAdditionalInformation($info)
        {
            $this->additionalInformation = $info;

            return $this;
        }

        public function getModuleName()
        {
            return $this->moduleName;
        }

        public function getCallToActionText()
        {
            return $this->callToActionText;
        }

        public function getAction()
        {
            return $this->action;
        }

        public function getAdditionalInformation()
        {
            return $this->additionalInformation;
        }
    }
}
