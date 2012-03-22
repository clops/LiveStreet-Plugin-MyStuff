<?php

    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_ActionMystuff extends ActionPlugin {
    

        /***
         *  Le constructor
         ***/
        public function Init() {    
            $this->SetDefaultEvent('mytopics'); 
            $this->Viewer_AddHtmlTitle($this->Lang_Get('my_stuff'));
        }

        
        /***
         *  Per Default call the event EventIndex
         ***/
        protected function RegisterEvent() {
            $this->AddEvent('index','EventIndex');
            $this->AddEvent('personal','EventIndex');
            $this->AddEvent('friends','EventIndex');
            $this->AddEvent('new','EventNew');
            $this->AddEventPreg('/mytopics/','/^(page(\d+))?$/i','EventMyTopics');
            $this->AddEventPreg('/mycomments/','/^(page(\d+))?$/i','EventMyComments');
        }
        
        
        protected function EventMyTopics() {
					$iPage=$this->GetParamEventMatch(0,2) ? $this->GetParamEventMatch(0,2) : 1;	

					$aFilter=array(			
						'user_id' => $this->User_GetUserCurrent()->getId(),
						'order' => 't.topic_last_update desc'
					);
					
					$aResult=$this->PluginMystuff_ModuleMystuff_GetMyTopics($this->User_GetUserCurrent(),$iPage,Config::Get('module.topic.per_page'));
					$aTopics=$aResult['collection'];

					$aPaging=$this->Viewer_MakePaging($aResult['count'],$iPage,Config::Get('module.topic.per_page'),4,Router::GetPath('mine/mytopics'));		

					$this->Viewer_Assign('aPaging',$aPaging);			
					$this->Viewer_Assign('aTopics',$aTopics);

        	$this->Viewer_Assign('sMenuItemSelect','mytopics');    	
        	$this->Viewer_Assign('menu', 'mystuff');
        }

        protected function EventMyComments() {
					$iPage=$this->GetParamEventMatch(0,2) ? $this->GetParamEventMatch(0,2) : 1;	

					$aResult=$this->PluginMystuff_ModuleMystuff_GetMyCommentedTopics($iPage,Config::Get('module.topic.per_page'));
					$aTopics=$aResult['collection'];

					$aPaging=$this->Viewer_MakePaging($aResult['count'],$iPage,Config::Get('module.topic.per_page'),4,Router::GetPath('mine/mycomments'));		

					$this->Viewer_Assign('aPaging',$aPaging);			
					$this->Viewer_Assign('aTopics',$aTopics);

        	$this->Viewer_Assign('sMenuItemSelect','mycomments');    	
        	$this->Viewer_Assign('menu', 'mystuff');
        }

        /***
         *  Default Event
         ***/
        protected function EventIndex($newOnly=false) {

            //visible to logged in users only
            if (!$this->User_GetUserCurrent()) {			
                return $this->EventNotFound();
            }
        	
            if(!$this->aParamsEventMatch['event'][0] OR $this->aParamsEventMatch['event'][0]=="index")
            	$event0 = "personal";
            else
            	$event0 = $this->aParamsEventMatch['event'][0];
            	
        		switch($event0)
        		{
        		case "personal":
        		case "friends":
        			$who=$event0;
        			break;
        		default:
        			return $this->EventNotFound();
        		}
        		
        		$this->Viewer_Assign('sMenuItemSelect', $who);
        		
        		if(!$this->GetParam(0))
        			$param0 = "all";
        		else
        			$param0 = $this->GetParam(0);
        		
        		switch($param0)
        		{
        		case "new":
        			$newOnly = 1;
        			break;
        		case "all":
        			$newOnly = 0;
        			break;
        		default:
        			return $this->EventNotFound();
        		}
        		
        		$this->Viewer_Assign('sMenuSubItemSelect', $param0);
        		
        		if($who=="friends")
        		{
        			if(!$this->GetParam(1))
        				$param1 = Config::Get('plugin.friends.feed.filter.default');
        			else
        				$param1 = $this->GetParam(1);
        			
							switch($param1)
							{
							case "close":
								$level = 10;
								break;
							case "normal":
								$level = 0;
								break;
							case "distant":
								$level = -10;
								break;
							default:
								return $this->EventNotFound();
							}
							
							$this->Viewer_Assign('sMyStuffFilter', $param1);		
							
							$this->Viewer_Assign('sRouting', "mine/friends/".$param0);
						}

            $this->SetTemplateAction('index');
            
            //load data from db
            switch($who)
            {
            case "personal":
            	$aResult = $this->PluginMystuff_ModuleMystuff_GetTopics(false, $newOnly );
            	break;
            case "friends":
            	$aResult = $this->PluginMystuff_ModuleMystuff_GetTopicsByFriend(false, $newOnly, $level );
            	break;
            }
            $aTopics = $aResult['collection'];
            
            //pass it to smarty
            $this->Viewer_Assign('aTopics',$aTopics);
            $this->Viewer_Assign('menu', 'mystuff');
        }
        
        
        /***
         *  Tiny filter to acttually filter the list we have
         ***/
        protected function EventNew() {
            $this->Viewer_Assign('sMenu','new');
            return $this->EventIndex(true);
        }

        
        /***
         *  Shutdown Function
         ***/
        public function EventShutdown() {
        		$this->Viewer_Assign('iMyTopicsCountUnreadComments',$this->PluginMystuff_ModuleMystuff_GetMyTopicsCountUnreadComments());		
        		$this->Viewer_Assign('iMyCommentedTopicsCountUnreadComments',$this->PluginMystuff_ModuleMystuff_GetMyCommentedTopicsCountUnreadComments());
            dump('MyStuff Event Completed');
        }
    }
