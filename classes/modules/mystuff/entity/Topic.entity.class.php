<?php

    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_ModuleMystuff_EntityTopic extends ModuleTopic_EntityTopic {
                                                       // this is also rather wicked in the way inheritance is processed?
    
        /***
         *  Data Getter
         ***/
        public function getId() {
            return $this->_aData['topic_id'];
        }
        
        /***
         *  Data Getter
         ***/
        public function getLastUpdate() {
            return $this->_aData['topic_last_update'];
        }
        
        /***
         *  Data Setter
         ***/
        public function setId($data) {
            $this->_aData['topic_id'] = $data;
        }
        
        /***
         *  Data Setter
         ***/
        public function setLastUpdate($data) {
            $this->_aData['topic_last_update'] = $data;
        }
    }
