<?php

    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_ModuleTopic extends ModuleTopic {
        
        /***
         *  Use a Custom Mapper
         ***/
        public function Init() {		
            parent::Init(); //init all parent stuff
            $this->oMapperTopic = Engine::GetMapper('PluginMystuff_ModuleTopic'); //this is essential, otherwise the default mapper is used, which we do not want
            $this->oMapperTopic->SetUserCurrent($this->oUserCurrent);
        }
        
        
        /***
         *  From the list of Marked topics for mystuff narrow the list down to new topics only
         ***/
        public function GetOnlyUnreadTopicsFromList(Array $topicList,$type){
            return $this->oMapperTopic->GetOnlyUnreadTopicsFromList($topicList,$type);
        }
	
    }
    