<?php
    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_ModuleMystuff extends Module {
    
        //database access layer 
        protected $oMapper;
    
        
        /***
         * Constructor
         ***/
        public function Init() {    
            $this->oMapper      = Engine::GetMapper(__CLASS__); //get me my DB link baby
            $this->oUserCurrent = $this->User_GetUserCurrent(); //do I need this?
        }
    
        
        /***
         * Pass the update to the database 
         ***/
        public function MarkTopicWithTimestamp($oTopic) {  
            return $this->oMapper->MarkTopicWithTimestamp($oTopic);
        }
        
        
        /***
         *  Add a topic to the list of topics I have commented on (if not there already)
         ***/
        public function MarkTopicInMyStuff($oTopic) {
            return $this->oMapper->MarkTopicInMyStuff($this->oUserCurrent, $oTopic);
        }
        
        
        /***
         *  Get a list of topics that my frieds commented on
         *  and order them in the order of the latest comment added
         ***/
        public function GetTopicsByFriend($countOnly=false, $newOnly=false, $level) {
            //these are the topics relevant for MyStuff list
            $myStuffTopics = $this->oMapper->getTopicIDsForFriendsStuff($this->oUserCurrent,$level);
            
            return $this->ProcessMyStuffTopics($countOnly, $newOnly, $myStuffTopics, "friends");
        }
        
        public function GetTopics($countOnly=false, $newOnly=false) {
            //these are the topics relevant for MyStuff list
            $myStuffTopics = $this->oMapper->getTopicIDsForMyStuff($this->oUserCurrent);
            
            return $this->ProcessMyStuffTopics($countOnly, $newOnly, $myStuffTopics, "mine");
        }
                
        
        private function ProcessMyStuffTopics($countOnly, $newOnly, $myStuffTopics, $type)
        {  	
            //I need to filter this list to show only topics that have something NEW in them
            $reply = $this->PluginMystuff_ModuleTopic_GetOnlyUnreadTopicsFromList($myStuffTopics,$type);                
            $this->Viewer_Assign('myStuffNewComments', $reply['newComments']);
            $this->Viewer_Assign('myStuffNewTopics', $reply['newTopics']);
            if($newOnly){
                $myStuffTopics = $reply['topics'];                
            }
            
            //build the filter (cache works based on the filter as key)
            $aFilter=array(
                'blog_type'     => array(
                                        'personal',
                                        'open',
                                        'subcat'
                                    ),
                'topic_publish' => 1,
                'topic_publish_index' => 1,
                'topic_id'      => $myStuffTopics,
                'order'         => 't.topic_last_update desc'    
            );
    
            if($this->oUserCurrent) {
                $aOpenBlogs = $this->Blog_GetAccessibleBlogsByUser($this->oUserCurrent);
                if(count($aOpenBlogs)) $aFilter['blog_type']['close'] = $aOpenBlogs;
            }
    
            if($countOnly){
                return $this->PluginMystuff_ModuleTopic_GetCountTopicsByFilter($aFilter);
            }
            
            return $this->PluginMystuff_ModuleTopic_GetTopicsByFilter($aFilter);
        }
        
        
        /***
         *  Calculate the number of unread topics by some user in MyStuff
         ***/
        public function GetNumberOfUnreadTopics() {
            //$topicsWrittenByFriends = $this->GetTopicsByFriend(true);
            //return $topicsWrittenByFriends;
        }
        
        public function GetMyCommentedTopics($iPage,$iPerPage) {
        	$aAllowData=array
        			(
        			'user'=>array(),
        			'blog'=>array('owner'=>array(),'relation_user'),
        			'vote',
        			'favourite',
        			'comment_new'
        			);
        	
					if (false === ($data = $this->Cache_Get("my_commented_topics_{$iPage}_{$iPerPage}"))) {			
						$data = array(
									'collection'=>$this->oMapper->GetMyCommentedTopics($this->oUserCurrent,$iCount,$iPage,$iPerPage),
									'count'=>$iCount
								);
						$this->Cache_Set($data, "my_commented_topics_{$iPage}_{$iPerPage}", array('topic_update','topic_new'), 60*60*24*3);
					}
					$data['collection']=$this->Topic_GetTopicsAdditionalData($data['collection'],$aAllowData);
					
					return $data;
        }
        
				public function GetMyTopics($oUser,$iPage=0,$iPerPage=0,$aAllowData=array('user'=>array(),'blog'=>array('owner'=>array(),'relation_user'),'vote','favourite','comment_new')) {

					if (false === ($data = $this->Cache_Get("mystuff_mytopics_filter_{".$oUser->getId()."}_{$iPage}_{$iPerPage}"))) {			
						$data = array(
									'collection'=>$this->oMapper->GetMyTopics($oUser,$iCount,$iPage,$iPerPage),
									'count'=>$iCount
								);

						$this->Cache_Set($data, "topic_filter_{".$oUser->getId()."}_{$iPage}_{$iPerPage}", array('topic_update','topic_new'), 60*60*24*3);
					}
					$data['collection']=$this->Topic_GetTopicsAdditionalData($data['collection'],$aAllowData);
					return $data;
				}
        
        
        public function GetMyCommentedTopicsCountUnreadComments() {
        	return $this->oMapper->GetMyCommentedTopicsCountUnreadComments($this->oUserCurrent);
        }

        public function GetMyTopicsCountUnreadComments() {
        	return $this->oMapper->GetMyTopicsCountUnreadComments($this->oUserCurrent);
        }
    }
    