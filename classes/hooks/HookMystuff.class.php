<?php

    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_HookMystuff extends Hook {
    
        /***
         * Suck on the hooks for comment creation and topic creation
         ***/
        public function RegisterHook() {            
            $this->AddHook('topic_add_after', 'AddLastUpdate', __CLASS__);             
            $this->AddHook('comment_add_after', 'UpdatedComment', __CLASS__);         
            
            //$this->AddHook('template_userbar_item','Menu');
            if($oUserCurrent=$this->User_GetUserCurrent()){
                $this->AddHook('template_main_menu','Menu', __CLASS__);
            }
        }

        
        /***
         * Mark the topic last_modified date when the topic is created
         * @param $aVars -- All the cool stuff passed to the hook such as the topic et cetera
         ***/       
        public function AddLastUpdate($aVars) {
            $oTopic = $this->getTopic();
            $oTopic->setId($aVars['oTopic']->getId()); //set the topic ID for the update
            $oTopic->setLastUpdate($aVars['oTopic']->getDateAdd()); //set the date that will be used
            return $this->updateTopic($oTopic);
        }
        
        
        /***
         * Mark the topic last_modified_date when a comment is created for that topic
         * @param $aVars -- All the cool stuff passed to the hook such as the topic et cetera
         ***/
        public function UpdatedComment($aVars) {
            $oTopic = $this->getTopic();             
            $oTopic->setId($aVars['oTopic']->getId()); //set the topic id
            $oTopic->setLastUpdate($aVars['oCommentNew']->getDate()); //get the date from the comment and push it to the topic
            return $this->updateTopic($oTopic);
        }
        
        
        /***
         * Get the MyStuff Entity Object
         ***/
        protected function getTopic() {
            return Engine::GetEntity('PluginMystuff_ModuleMystuff_EntityTopic'); //this is ok, the engine knows where to look for that file
        }
        
        
        /***
         * Do the DB update
         ***/
        protected function updateTopic(PluginMystuff_ModuleMystuff_EntityTopic $oTopic) {
            // Now, this is calling the controller/module class, function AddLastUpdate
            // the controller in turn passed data to the Model (Mapper) -- ak --
            $this->PluginMystuff_ModuleMystuff_MarkTopicWithTimestamp($oTopic); //execute the change            
            $this->PluginMystuff_ModuleMystuff_MarkTopicInMyStuff($oTopic);
            
            //per default
            return true;
        }
        
        
        /***
         *  Add an entry in the main menu with link to My Stuff
         ***/
        public function Menu() {
            $this->Viewer_Assign('unreadTopics', $this->PluginMystuff_ModuleMystuff_GetNumberOfUnreadTopics());
            return $this->Viewer_Fetch('main_menu_mystuff.tpl');
        }
        
    } //class   