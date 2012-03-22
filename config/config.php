<?php
//address router
Config::Set('router.page.mine', 'PluginMystuff_ActionMystuff');

//database setting
$config['table']['topic_commented'] = '___db.table.prefix___topic_commented';

$config['max_age_in_weeks'] = 52; // maximum age of a topic to show in mystuff 

return $config;
?>