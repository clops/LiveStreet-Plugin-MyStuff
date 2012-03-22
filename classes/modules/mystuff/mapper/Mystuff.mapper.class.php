<?php

    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_ModuleMystuff_MapperMystuff extends Mapper {
        
        /***
         *  Mark the neccessary table for update
         ***/
        public function MarkTopicWithTimestamp($oTopic) {        
            $sql = "UPDATE ".Config::Get('db.table.topic')."
                        SET
                            topic_last_update = ?            
                        WHERE
                            topic_id = ?d
                    ";
                        
            if ($this->oDb->query($sql, $oTopic->getLastUpdate(), $oTopic->getId())) {                
                return true;
            }
            return false;
        }
        
        
        
        /***
         *  Mark a Topic for use in My Stuff
         ***/
        public function MarkTopicInMyStuff($oUser, $oTopic){
            $sql = "SELECT 
                        topic_id 
                    FROM 
                        ".Config::Get('plugin.mystuff.table.topic_commented')."
                    WHERE
                        user_id     = ?d AND
                        topic_id    = ?d ";
                        
            
            //basucally check if there is an entry in the db for these settings
            //and if there is none, go and make one
            if(!$this->oDb->selectCell($sql, $oUser->getId(), $oTopic->getId())) { //if the entry is NOT yet marked
            
                $sql = "INSERT INTO ".Config::Get('plugin.mystuff.table.topic_commented')." SET
                        user_id     = ?d ,
                        topic_id    = ?d ";
            
                //making the entry here
                if(!$this->oDb->query($sql, $oUser->getId(), $oTopic->getId())) {
                    return true;
                }                
            }
            
            return false;
        }
        
        
        /***
         *  Returns a list of topic IDs that belong to "my stuff"
         *  @return Array()
         ***/
        public function getTopicIDsForFriendsStuff($oUser,$level){
            $sql = "SELECT 
                        user_to 
                      FROM ".Config::Get('db.table.friend')." 
                        WHERE 
                            user_from = ?d 
                            AND ((status_from = 2 AND status_to = 2) OR (status_from + status_to = 3)) 
                            AND group_from >= ".$level."
                    UNION 
                     SELECT 
                        user_from 
                      FROM ".Config::Get('db.table.friend')."
                        WHERE 
                            user_to = ?d
                            AND ((status_from = 2 AND status_to = 2) OR (status_from + status_to = 3))
                            AND group_to >= ".$level."";
                                      
            //first, get a list of all my friends
            $friends    = $this->oDb->selectCol($sql, $oUser->getId(), $oUser->getId());
            //$friends[]  = $oUser->getId(); //I am my own friend :-) <- this doesn't make sense
            $friends[] = 0;
            
dump('My friends are: '.print_r($friends, true));

            //now get a list of topics my friends commented on no more than 4 weeks ago
            $sql = "SELECT
                        t.topic_id 
                    FROM 
                        ".Config::Get('db.table.topic')." t LEFT JOIN
                        ".Config::Get('plugin.mystuff.table.topic_commented')." tc ON (tc.topic_id = t.topic_id)
                        ".$this->GetFriendsJoin($oUser)."
                    WHERE
                        (tc.user_id IN (?a) OR t.user_id IN (?a)) 
                        AND (tc.created >= DATE_SUB(NOW(), INTERVAL ".Config::Get('plugin.mystuff.max_age_in_weeks')." WEEK) OR t.topic_date_add >= DATE_SUB(NOW(), INTERVAL ".Config::Get('plugin.mystuff.max_age_in_weeks')." WEEK))
                        AND t.topic_type != 'teaser'
                        AND t.topic_type != 'pinboard'
                        ".$this->GetFriendsWhere($oUser)."
                    ";
                    
            if($topics = $this->oDb->selectCol($sql, $friends, $friends)){
                $topics = array_unique($topics);
                dump('My Stuff Topics are: '.print_r($topics, true));
                return $topics;
            }
            
            //fallback
            return array();
        }
    

        /***
         *  Returns a list of topic IDs that belong to "my stuff"
         *  @return Array()
         ***/
        public function getTopicIDsForMyStuff($oUser){

            //now get a list of topics my friends commented on no more than 4 weeks ago
            $sql = "SELECT
                        t.topic_id 
                    FROM 
                        ".Config::Get('db.table.topic')." t 
                        LEFT JOIN ".Config::Get('plugin.mystuff.table.topic_commented')." tc ON (tc.topic_id = t.topic_id)
                        ".$this->GetFriendsJoin($oUser)."
                    WHERE
                        (tc.user_id = ".$oUser->getId()." OR t.user_id = ".$oUser->getId().") 
                        AND (tc.created >= DATE_SUB(NOW(), INTERVAL ".Config::Get('plugin.mystuff.max_age_in_weeks')." WEEK) OR t.topic_date_add >= DATE_SUB(NOW(), INTERVAL ".Config::Get('plugin.mystuff.max_age_in_weeks')." WEEK))
                        AND t.topic_type != 'teaser'
                        AND t.topic_type != 'pinboard'
                        ".$this->GetFriendsWhere($oUser)."
                    ";

            if($topics = $this->oDb->selectCol($sql)){
                $topics = array_unique($topics);
                dump('My Stuff Topics are: '.print_r($topics, true));
                return $topics;
            }
            
            //fallback
            return array();
        }
        
				protected function GetFriendsJoin($oUser)
				{
					return "
					LEFT JOIN ".Config::Get('db.table.friend')." AS f_from ON ( f_from.user_from = t.user_id AND f_from.user_to = ".$oUser->getId()." )
					LEFT JOIN ".Config::Get('db.table.friend')." AS f_to ON ( f_to.user_to = t.user_id AND f_to.user_from = ".$oUser->getId()." )";
				}
				
				protected function GetFriendsWhere($oUser)
				{
					return "
					AND 
					(
						(
							f_from.user_to = ".$oUser->getId()." 
							AND f_from.group_from >= t.topic_permission_level
						)
						OR 
						(
							f_to.user_from = ".$oUser->getId()."
							AND f_to.group_to >= t.topic_permission_level
						)
						OR 
							t.user_id = ".$oUser->getId()."
						OR
							t.topic_permission_level = ".Config::Get('plugin.blog.permission_level.public')."
					)";
				}

				public function GetMyTopics($oUser,&$iCount,$iCurrPage,$iPerPage) {
					
					$sql = "SELECT 
									t.topic_id							
								FROM 
									".Config::Get('db.table.topic')." as t
									".$this->GetFriendsJoin($oUser).",
									".Config::Get('db.table.blog')." as b	
								WHERE 
									t.blog_id=b.blog_id
									AND t.topic_type != 'teaser'
									AND t.topic_type != 'pinboard'
									AND t.user_id = ".$oUser->getId()."
									".$this->GetFriendsWhere($oUser)."
								ORDER BY t.topic_last_update desc
								LIMIT ?d, ?d";		
					$aTopics=array();
					if ($aRows=$this->oDb->selectPage($iCount,$sql,($iCurrPage-1)*$iPerPage, $iPerPage)) {			
						foreach ($aRows as $aTopic) {
							$aTopics[]=$aTopic['topic_id'];
						}
					}				
					return $aTopics;
				}
				
				public function GetMyCommentedTopics($oUser,&$iCount,$iCurrPage,$iPerPage) {
					
					$sql = "SELECT 
									t.topic_id							
								FROM 
									".Config::Get('db.table.topic')." as t
									".$this->GetFriendsJoin($oUser).",
									".Config::Get('db.table.blog')." as b,
									".Config::Get('db.table.comment')." as tc
								WHERE 
									t.blog_id=b.blog_id
									AND t.topic_type != 'teaser'
									AND t.topic_type != 'pinboard'
									AND tc.target_id = t.topic_id
									AND tc.target_type = 'topic'
									AND t.topic_publish = '1'
									AND tc.user_id = ".$oUser->getId()."
									AND tc.comment_publish = '1'
									AND tc.comment_delete = '0'
									".$this->GetFriendsWhere($oUser)."
								GROUP BY t.topic_id
								ORDER BY t.topic_last_update desc
								LIMIT ?d, ?d";		
					$aTopics=array();
					if ($aRows=$this->oDb->selectPage($iCount,$sql,($iCurrPage-1)*$iPerPage, $iPerPage)) {			
						foreach ($aRows as $aTopic) {
							$aTopics[]=$aTopic['topic_id'];
						}
					}				
					return $aTopics;
				}
        
				public function GetMyTopicsCountUnreadComments($oUser) {
					
					$sql = "SELECT 
									sum(t.topic_count_comment - if(comment_count_last IS NULL, 0, tr.comment_count_last) ) as count
								FROM 
									".Config::Get('db.table.topic')." as t
									LEFT JOIN ".Config::Get('db.table.topic_read')." as tr ON (tr.topic_id = t.topic_id AND tr.user_id = ". $oUser->getId()." )
								WHERE
									t.user_id = ".$oUser->getId()."
									AND t.topic_type != 'teaser'
									AND t.topic_type != 'pinboard'
								";
					if ($aRow=$this->oDb->selectRow($sql)) {
						return $aRow['count'];
					}
					return 0;
				}

				public function GetMyCommentedTopicsCountUnreadComments($oUser) {
					
					$sql = "SELECT 
									sum(t.topic_count_comment - if(comment_count_last IS NULL, 0, tr.comment_count_last) ) as count
								FROM 
									".Config::Get('db.table.topic')." as t
									LEFT JOIN ".Config::Get('db.table.topic_read')." as tr ON (tr.topic_id = t.topic_id AND tr.user_id = ".$oUser->getId().")
								WHERE
									t.topic_id IN 
									(
										SELECT 
											t.topic_id							
										FROM 
											".Config::Get('db.table.topic')." as t
											".$this->GetFriendsJoin($oUser).",
											".Config::Get('db.table.blog')." as b,
											".Config::Get('db.table.comment')." as tc
										WHERE 
											t.blog_id=b.blog_id
											AND t.topic_type != 'teaser'
											AND t.topic_type != 'pinboard'
											AND tc.target_id = t.topic_id
											AND tc.target_type = 'topic'
											AND t.topic_publish = '1'											
											AND tc.user_id = ".$oUser->getId()."
											AND tc.comment_publish = '1'
											AND tc.comment_delete = '0'	
											".$this->GetFriendsWhere($oUser)."
									)
								";
					if ($aRow=$this->oDb->selectRow($sql)) {
						return $aRow['count'];
					}
					return 0;
				}

    }
