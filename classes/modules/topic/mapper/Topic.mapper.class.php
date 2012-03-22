<?php

    /*********************************************************
    *
    * @author Kulikov Alexey <ak@essentialmind.com>
    * @copyright essentialmind gmbh
    * @since 2010-07-01
    *
    *********************************************************/
    class PluginMystuff_ModuleTopic_MapperTopic extends ModuleTopic_MapperTopic {
        
        //currently logged in user (if any)
        protected $oUserCurrent;
        
        
        /***
         *  Setter for the Current User in Mapper
         ***/
        public function SetUserCurrent($user){
            $this->oUserCurrent = $user;
        }
        
        
        /***
         *  Had to override the default function in order to get the sorting params correct
         ***/
        public function GetAllTopics($aFilter) {
            $sWhere=$this->buildFilter($aFilter);

            /* -- start core fix -- */
            if(isset($aFilter['order']) and !is_array($aFilter['order'])) {
                $aFilter['order'] = array($aFilter['order']);
            } else {
                $aFilter['order'] = array('t.topic_date_add DESC');
            }
            /* -- end core fix   -- */
		
            $sql = "SELECT 
                        t.topic_id							
                    FROM 
                        ".Config::Get('db.table.topic')." as t,	
                        ".Config::Get('db.table.blog')." as b			
                    WHERE 
                        1=1					
                        ".$sWhere."
                        AND
                        t.blog_id=b.blog_id					
                        AND t.topic_type != 'teaser'
                        AND t.topic_type != 'pinboard'
                    ORDER BY ".implode(', ', $aFilter['order']);		
            $aTopics=array();
            if ($aRows=$this->oDb->select($sql)) {			
                foreach ($aRows as $aTopic) {
                    $aTopics[]=$aTopic['topic_id'];
                }
            }		
    
            return $aTopics;		
        }
        
        
        /***
         *  Works with an array as input, removing all entries (topic IDs) that the user
         *  has already read and that have no new comments since then
         *
         *  @return Array with keys 'topics' => array of topic ids, 'newComments' => int and 'newTopics' => int
         ***/
        public function GetOnlyUnreadTopicsFromList(Array $topicList,$type){
            $topicList[]    = 0; //safety pin
            $sqlForRawTopicData = "SELECT 
                                        t.topic_id AS ARRAY_KEY,
                                        t.topic_count_comment AS comment_count
                                    FROM 
                                        ".Config::Get('db.table.topic')." AS t
                                    WHERE
                                        topic_id IN(?a)";
            
            $sqlForUserData     = "SELECT
                                        t.topic_id AS ARRAY_KEY,
                                        t.comment_count_last AS comment_count
                                    FROM 
                                        ".Config::Get('db.table.topic_read')." AS t
                                    WHERE
                                        topic_id IN(?a) AND
                                        user_id = ?d";
                                        
            $list1 = $this->oDb->select($sqlForRawTopicData, $topicList);
            $list2 = $this->oDb->select($sqlForUserData, $topicList, $this->oUserCurrent->getId());
            
            $toReturn = array();
            $commentCountDifference = 0;
            $postDifference         = 0;
            foreach($topicList as $topicID){
                if(!$topicID) continue;
                
                if(!isset($list2[$topicID]) and $type!="mine")
                	$list2[$topicID]["unread"] = 1;
                	
                if(!isset($list2[$topicID]['comment_count'])){
                    $postDifference++;
                    $list2[$topicID]['comment_count'] = 0;
                }
                
                if($list1[$topicID]['comment_count'] > $list2[$topicID]['comment_count'] OR isset($list2[$topicID]["unread"])){
                    $toReturn[] = $topicID;
                    $commentCountDifference = $commentCountDifference + ($list1[$topicID]['comment_count'] - $list2[$topicID]['comment_count']);
                }
            }
            
            //$this->Viewer_Assign('myStuffNewItems',12);
            
            //print_r($toReturn);

            return array('topics' => $toReturn, 'newComments' => $commentCountDifference, 'newTopics' => $postDifference);
        }
        
        
        /***
         *  Basically allowing to add a list of topic IDs to the filter, thus the extension
         ***/
        protected function buildFilter($aFilter) {
            $where = parent::buildFilter($aFilter); //we are extending the core, right?

            //this makes sure there is NO content listed in case there are no entries commented by me or my friends
            if(empty($aFilter['topic_id'])){
                $aFilter['topic_id'] = 0;
            }
            
            //extend the default filter
            if(isset($aFilter['topic_id'])){
                $aFilter['topic_id']    = (array)$aFilter['topic_id'];
                $aFilter['topic_id'][]  = 0; //safety pin
                $where .= ' AND topic_id IN ('.implode(',', $aFilter['topic_id']).') ';
            }
            
            //debug output
            dump($where);
                        
            return $where;
        }
        
        
        /***
         *  This function keeps just unread topics in the topic list provided
         *  @param $topicList   Array
         *  @return             Array
         ***/
        protected function FilterTopicListByReadStatus(Array $topicList){
            $topicList = array_unique($topicList);            
            $sql = "SELECT
                        t.topic_id
                    FROM
                        ".Config::Get('db.table.topic_read')." AS t
                    WHERE 
                        topic_id IN (?a)
                        AND
                        user_id = ?d";
            if($topicsReadByUser = $this->oDb->selectCol($sql, $topicList, $this->oUserCurrent->getId())){
                return array_diff($topicList, $topicsReadByUser);
            }
            return $topicList;
        }

    } //end class
