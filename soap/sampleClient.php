<?php 
// Just an example of a PHP SOAP client for Sympa. You can change to another web service
// of Sympa without difficulty (look at the API of nusoap if you want to learn more).
//
// This need the SOAP library for PHP located at : http://dietrich.ganx4.com/nusoap
// You also need to customize the $soapServer variable

include('/home/httpd/test/nusoap.php');

require_once('nusoap.php');

$soapclient->debug_flag=true;

global $userEmail;
global $md5;
global $soapServer;
$soapServer = "http://www.cru.fr/wws/wsdl";

// $parameters = array($list,$email);
$soapclient = new soapclient($soapServer,'wsdl');

ob_start();
echo "<html> <body BGCOLOR=\"#000099\" TEXT=\"#cccccc\" LINK=\"#ff9933\" ALINK=\"#ff9933\" VLINK=\"#ff9933\"> \n";

if ($_COOKIE['sympaEmail'] && $_COOKIE['sympaMd5']) {

  // LOGOUT
  if ($_GET['logout'] == 1) {
    setcookie ("sympaEmail", "", time() - 3600);
    setcookie ("sympaMd5", "", time() - 3600);
    echo "<P ALIGN=\"center\"><FONT COLOR=\"#ff0000\">Logged out</FONT></P>\n";
  }else {  
    $userEmail = $_COOKIE['sympaEmail'];
    $md5 = $_COOKIE['sympaMd5'];
  }

  // LOGIN
}elseif ($_POST['email'] && $_POST['pwd']) {
  $md5 = $soapclient->call('login',array($_POST['email'],$_POST['pwd']));

  if (gettype($md5) == "string") {
    $userEmail = $_POST['email'];
    
    setcookie("sympaEmail",$userEmail);
    setcookie("sympaMd5",$md5);
    
  }else {
    echo "<P ALIGN=\"center\"><FONT COLOR=\"#ff0000\">Authentication failed</FONT></P>\n";
  }
}

if (isset($userEmail)) {
      echo "<FONT COLOR=\"99ccff\">logged in as ".$userEmail."</FONT><BR>\n";
      echo "[<A HREF=\"".$_SERVER['PHP_SELF']."?logout=1\">logout</A>]\n";

}else {
    echo "You need to login first :<BR><BR>\n";
    echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
    Email:  <input type=\"text\" name=\"email\"><br>
    Password: <input type=\"password\" name=\"pwd\">
    <input type=\"submit\" name=\"submit\" value=\"Login\">
</form>
";
  }

if (isset($userEmail)) {

  // SIGNOFF
  if ($_GET['signoff'] == 1) {
    $res = $soapclient->call('authenticateAndRun',array($userEmail,$md5,'signoff',array($_GET['list'])));
    if (gettype($res) == 'array') {
      echo "<P ALIGN=\"center\"><FONT COLOR=\"#ff0000\">Unsubscription failed<BR>".$res['faultstring']." : ".$res['detail']."</FONT></P>\n";
    }else {
      echo "<P ALIGN=\"center\"><FONT COLOR=\"#99ccff\">Successfully unsubscribed</FONT></P>\n";
    }

    // SUBSCRIBE
  }elseif ($_GET['subscribe'] == 1) {
    $res = $soapclient->call('authenticateAndRun',array($userEmail,$md5,'subscribe',array($_GET['list'])));
    if (gettype($res) == 'array') {
      echo "<P ALIGN=\"center\"><FONT COLOR=\"#ff0000\">Subscription failed<BR>".$res['faultstring']." : ".$res['detail']."</FONT></P>\n";
    }else {
      echo "<P ALIGN=\"center\"><FONT COLOR=\"#99ccff\">Successfully subscribed</FONT></P>\n";
    }
  }


  // WHICH
  echo "<BR><BR>Mailing lists you are subscribed to :<DL>\n";
  //$res = $soapclient->call('authenticateAndRun',array($userEmail,$md5,'which'));
  $res = $soapclient->call('authenticateAndRun',array($userEmail,$md5,'complexWhich'));
  
  if (isset($res) && gettype($res) == 'array') {
    
    foreach ($res as $list) {
      echo "<DD>";
      list ($list['listName'],$list['listDomain']) = explode("@",$list['listAddress']);
      $subscribed[$list['listAddress']] = True;
      
      echo $list['listAddress']." [<A HREF=\"".$_SERVER['PHP_SELF']."?signoff=1&list=".$list['listName']."\">signoff</A>] [<A HREF=\"".$list['homepage']."\">info</A>] \n";
    }
    echo "</DL>\n";
  }else {
    echo "<DL><DD>No subscription</DL><BR>\n";
  }
  
  // LISTS
  echo "Other mailing lists :<DL>\n";
  $res = $soapclient->call('authenticateAndRun',array($userEmail,$md5,'complexLists'));
  
  if (isset($res)) {
    foreach ($res as $list) {
      echo "<DD>";
      list ($list['listName'],$list['listDomain']) = explode("@",$list['listAddress']);
      if (isset($subscribed[$list['listAddress']])) {
	next;
      }else {
	echo $list['listAddress']." [<A HREF=\"".$_SERVER['PHP_SELF']."?subscribe=1&list=".$list['listName']."\">subscribe</A>] [<A HREF=\"".$list['homepage']."\">info</A>] \n";
      }
    }
    echo "</DL>\n";
  }

}


ob_end_flush();

unset($soapclient); ?>

<P ALIGN="right">
<TABLE WIDTH="100%">
<TR><TD ALIGN="left">
<I><center>This is a sample PHP interface for Sympa using SOAP</center></I>
</TD><TD ALIGN="right">
<A HREF="http://www.sympa.org"><IMG BORDER="0" SRC="http://www.sympa.org/logos/logo-sympa-150x49.gif"></A></TD>
</TABLE>
</P>
 </body>
 </html>
