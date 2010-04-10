<?php

namespace Bogart;

class Exception
{
  public function __toString()
  {
    return $this->message;
  }
  
  public static function outputDebug()
  {  
    $log = Log::pretty();
    $color = strstr($log, 'Error') ? 'red' : 'black';
    echo "<div 
      onclick=\"el=document.getElementById('bogart_log_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display = 'block';}\"
      style=\"border-bottom: 2px solid {$color}; border-left: 2px solid {$color}; position: fixed; top: 0; right: 0; background-color: white; text-align: right; -webkit-border-bottom-left-radius: 10px; -moz-border-radius-bottomleft: 10px; border-bottom-left-radius: 10px;\"
      ><a href=\"javascript::void(0);\" style=\"text-decoration:none; color: red;\">&nbsp;&#x272A; debug</a>";
    echo "<div id='bogart_log_container' style=\"height: 500px; width: 1000px; display: none; overflow: scroll; padding: 0.5em; text-align: left;\">";
    echo $log;
    echo "</div></div>";
  }
}