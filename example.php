<?php
/**
 * Created by PhpStorm.
 * User: levsion
 * Date: 2016/8/5
 * Time: 2:56 PM
 */

//配置log常量
if(IS_ONLINE_SERVER)
{
    //define('NEW_LOG_HOST','');
    define('NEW_LOG_PORT','5151');
    define('NEW_LOG_CHANNEL','mapi');
    define('NEW_LOG_EXECUTE_TIME',0.1);
    define('NEW_LOG_REQUEST',false);
    define('NEW_LOG_REQUEST_PARAM',false);
}else if(IS_DEV_SERVER)
{
    //define('NEW_LOG_HOST','');
    define('NEW_LOG_PORT','5152');
    define('NEW_LOG_CHANNEL','mapi');
    define('NEW_LOG_EXECUTE_TIME',0);
    define('NEW_LOG_REQUEST',true);
    define('NEW_LOG_REQUEST_PARAM',true);
}else{
    //define('NEW_LOG_HOST','');
    define('NEW_LOG_PORT','5153');
    define('NEW_LOG_CHANNEL','mapi');
    define('NEW_LOG_EXECUTE_TIME',0);
    define('NEW_LOG_REQUEST',true);
    define('NEW_LOG_REQUEST_PARAM',true);
}

new_log::log("hello new_log",'log_key');
