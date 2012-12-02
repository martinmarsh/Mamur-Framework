<?php
namespace mamur\services\controller;

class admin extends abstractController{
    
    
    
     /* The get action returns the contents in xml of
     * the file in
     */
    public function render(){
        
        $config=\mamur\config::get();
        $public=$config->publicUri;
        $style="$public/admin/css/main.css";
        $java="$public/admin/js/main.js";
        print <<<xxxAAA
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Built in Admin (Ajax Version)</title>
<link rel="stylesheet" href="{$style}" />
<meta name="keywords" content="" />
<meta name="description" content="" />
<meta name="generator" content="Mamau - mamur.org" />
<meta name="copyright" content="copyright (c) 2012 Sygenius Ltd" />
<meta name="google-site-verification" content="" />
<script src="{$java}" type="text/javascript">

</script>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<script type="text/javascript">
   var ajax=false;
   var ajaxstatus="confirmed";
</script>

</head>

<body onload="javascript:if (typeof(Start)==='function') Start();">
<div id="monitorMessage"></div>
</body>
</html>
   
xxxAAA;
        
    }
    
public function get(){
        
        $config=\mamur\config::get();
        $public=$config->publicUri;
        $style="$public/admin/css/main.css";
       // $java="$public/admin/js/main.js";
        print <<<xxxAAA
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Built in Admin (Ajax Version)</title>
<link rel="stylesheet" href="{$style}" />
<meta name="keywords" content="" />
<meta name="description" content="" />
<meta name="generator" content="Mamau - mamur.org" />
<meta name="copyright" content="copyright (c) 2012 Sygenius Ltd" />
<meta name="google-site-verification" content="" />


</script>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

</head>

<body onload="javascript:if (typeof(Start)==='function') Start();">
<div id="monitorMessage"></div>

<form action="/test" method="post" >

<input id="xxx" value="123" />
<input type="submit" />

</form>

</body>
</html>
   
xxxAAA;
        
    }
        
    
}