
var testObj0;
var alertStatusObj;
var alertTimer;
var alertFailedTimer;
var alertdone;
var replyrate=10000;  //10 sec
var fontmin=12;
var fontmax=24;
var sentbyajax=false;
var sendingQ=new Array();


function Start(){
    //ajaxCreateRequestObject(0);
    //testObj0=ajaxCreateRequestObject();
    alertStatusObj =ajaxCreateRequestObject();
    cartStatusObj =ajaxCreateRequestObject();
    cartStatusScrollObj =ajaxCreateRequestObject();
    //initial time to set monitor
    /*
    if(ajaxstatus=='unchecked'){
        alertTimer=setTimeout("checkdisplay()",1000); //1 sec delay
        ajax=false;
    }else if(ajaxstatus=='confirmed'){
        ajax=true;
        alertTimer=setTimeout("checkdisplay()",5000);  //first is 5s
    }

*/
   // if (typeof(elementStart)==='function') elementStart();

     //ajaxCreateRequestObject(1);
    //ajaxPost(1,"/netpublic/myscript.sys?test=getwins&hffuehfuew=890","test=postwins&gfh=9454939437973957943",testcallback0);
   // ajaxGet(alertStatusObj,"/test",testcallback1);
    query="x=12133213156Ab&y=dfefeff"; 
    //GET,PUT,DELETE,POST
    ajaxRest(alertStatusObj,"/test","PUT",query,"unauth","akncnje-my.domain.com",testcallback1);

}


function  noajax(){
  if(alertdone==0){
      if(ajax){
         alertTimer=setTimeout("checkdisplay()",5000);
      }
      ajax=false;
      //alert("timout - ajax failed");

     //document.getElementById('monitorImage').src="/admin/images/redtitledot.gif";
     document.getElementById('monitorMessage').className= "alertText";
     document.getElementById('monitorMessage').innerHTML= "No Response form Server";
  }
}

function alertstatusreply(){
  xmlstatusreply(alertStatusObj);
}


function xmlstatusreply(StatusObj){
  if((reply=ajaxReply(StatusObj))!="**wait**"){

     alertdone=1;
     if(reply=="**failed**"){
         // document.getElementById('monitorImage').src="/admin/images/orangetitledot.gif";
        document.getElementById('monitorMessage').className= "warnText";
        document.getElementById('monitorMessage').innerHTML= "Ajax Alert Call Failed";
        //  alert (reply);
   }else if(reply=="no function available"){
         // document.getElementById('monitorImage').src="/admin/images/orangetitledot.gif";
        document.getElementById('monitorMessage').className= "warnText";
        document.getElementById('monitorMessage').innerHTML= "Ajax function not in script file";
        //alert (reply);
     }else{
        replies=ajaxReturn(reply);
        if(replies[0]!='ok'){
          ajax=false;
             //document.getElementById('monitorImage').src="/admin/images/orangetitledot.gif";
          document.getElementById('monitorMessage').className= 'warnText';
         //  replies[1]="Failed";
         //  replies[2]="Error returned by Script";
         //alert (reply);
        }else{
          //alert (reply);
           if(replies[1]=='toconfirm'){
             alertdone=0; 
             alertFailedTimer=setTimeout("noajax()",3000);  //3 sec time out
             //alert("to confirmed ajax"+ajax);
             ajaxGet(StatusObj,"/page/ajaxcheck.sys?verify=ajax",alertstatusreply);

           }else if(replies[1]=='confirmed'){
              ajax=true;
             // alert("confirmed ajax"+ajax);
           }else if(replies[1]=='message'){
              //document.getElementById('leftStatus').innerHTML= replies[2];
              //message does not write to banner
           }else if(replies[1]=='cart'){
             if(replies[2]=='1'){
              //  document.getElementById('bannerimg').innerHTML= replies[4];

               // document.getElementById('banner').className= replies[4];

                document.getElementById('cartStatus').innerHTML= replies[5];
                document.getElementById(replies[3]).innerHTML= replies[6];

             }
             if(cartSendingCount!=cartSentCount){
                       //sendurl=sendingQ.pop();
                       cartSendTimer=setTimeout("sendNextCart()",500);

             }else{
                 unBusyCartTimer=setTimeout("unBusyCart()",100);

             }

           }else if(replies[1]=='cartstatus'){
             if(replies[2]=='1'){
                document.getElementById('cartStatus').innerHTML= replies[3];
             }
             if(replies[4]=='1'){
               if(document.getElementById('catblock_ii_'+replies[5])){
                     document.getElementById('catblock_ii_'+replies[5]).innerHTML=replies[6];
               }
             }

           }
        }

     }
  }
}

function ajaxCreateRequestObject(){
    if (window.XMLHttpRequest) {
       var requestObj = new XMLHttpRequest();
       
    } else if (window.ActiveXObject) {
       var requestObj  = new ActiveXObject("Microsoft.XMLHTTP");
       
    }
    return requestObj;
}


function ajaxRest(requestObj,url,method,query,authKey,apiId,callback){
       var contentType = "application/x-www-form-urlencoded; charset=UTF-8";
        requestObj.open(method, url, true);
        requestObj.onreadystatechange = callback;
        requestObj.setRequestHeader('X_MAMUR_AUTH_KEY', authKey);
        requestObj.setRequestHeader('X_MAMUR_API_ID', apiId);
        requestObj.setRequestHeader("Content-Type", contentType);
        requestObj.send(query);
 
}



function ajaxGet(requestObj,url,callback){
        requestObj.open("GET", url, true);
        requestObj.onreadystatechange = callback;
        requestObj.setRequestHeader('X_MAMUR_AUTHKEY', "abcdefghjk");
 
        requestObj.send(null);
}

function ajaxPost(requestObj,url,callback,query){
    var status = false;
    var contentType = "application/x-www-form-urlencoded; charset=UTF-8";
    requestObj.open("post", url, true);
    requestObj.onreadystatechange = callback;
    requestObj.setRequestHeader("Content-Type", contentType);
    requestObj.send(query);
    status = true;
    return status;
}

function ajaxReply(requestObj){
    // only if req shows "complete"
    if (requestObj.readyState == 4) {
        // only if "OK"
        if (requestObj.status == 200) {
            return requestObj.responseText;
        } else {
            return "**failed**";
        }
    }
    return "**wait**";
}

function testcallback0(){
    reply=ajaxReply(alertStatusObj);
    document.getElementById('monitorMessage').className= "alertText";
     document.getElementById('monitorMessage').innerHTML= reply;
  
}

function testcallback1(){
    reply=ajaxReply(alertStatusObj);
   document.getElementById('monitorMessage').className= "alertText";
     document.getElementById('monitorMessage').innerHTML= reply;
 
}


function createQueryString(form){
    var elements = form.elements;
    var pairs = new Array();

    for (var i = 0; i < elements.length; i++) {

        if ((name = elements[i].name) && (value = elements[i].value))
            pairs.push(name + "=" + encodeURIComponent(value));
    }

    return pairs.join("&");
}


function ajaxReturn(xmltext){
  //returns an array of values if
  //xmltext=<vars><var>value returned</var><var>value 2 returned</var></vars>
  //or similar - tag names not important
   var xmlDoc;
   var response=new Array();
   xmlDoc=xmlParser(xmltext);
   response=xmlVars(xmlDoc);
   return response;
}


function xmlParser(text){

try //Internet Explorer
  {
  var xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
  xmlDoc.async="false";
  xmlDoc.loadXML(text);
  }
catch(e)
  {
  try //Firefox, Mozilla, Opera, etc.
    {
    var parser=new DOMParser();
    xmlDoc=parser.parseFromString(text,"text/xml");

    }
  catch(e) {alert(e.message)}
  }
 return xmlDoc;
}

function xmlVars(xmlDoc){
  //format of xml
  //<reply><var>value returned</var><var>value 2 returned</var></reply>
  var response=new Array();
  var x=xmlDoc.documentElement.childNodes;
  var j=0;
  for (i=0;i<x.length;i++){
    if(x[i].tagName=="var" && x[i].nodeType==1){
       response[j]=x[i].childNodes[0].nodeValue;
       j++;
    }
  }
  return response;
}







function setimage(id,img){
  document.getElementById(id).src=img;

}

function setclass(id,newclass){
  document.getElementById(id).className=newclass;

}

function view(URL){
    //alert(URL);
   day = new Date();
   id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=760,height=550,left = 50,top = 50');");

}

function getViewWidth() {
  var myWidth = 0;
  if( typeof( window.innerWidth ) == 'number' ) {
    //Non-IE
    myWidth = window.innerWidth;
  } else if( document.documentElement &&  document.documentElement.clientWidth) {

//IE 6+ in 'standards compliant mode'
    myWidth = document.documentElement.clientWidth;
  } else if( document.body && document.body.clientWidth ) {
    //IE 4 compatible
    myWidth = document.body.clientWidth;
  }
  return myWidth;
}

function getViewHeight() {
  var myHeight = 0;
  if( typeof( window.innerHeight ) == 'number' ) {
    //Non-IE
    myHeight = window.innerHeight;
  } else if( document.documentElement && document.documentElement.clientHeight) {
    //IE 6+ in 'standards compliant mode'
    myHeight = document.documentElement.clientHeight;
  } else if( document.body &&  document.body.clientHeight  ) {
    //IE 4 compatible
    myHeight = document.body.clientHeight;
  }
  return  myHeight;
}



function increaseFontSize() {
    var p = document.getElementsByTagName('p');
    for(i=0;i<p.length;i++) {
        if(p[i].style.fontSize) {
            var s = parseInt(p[i].style.fontSize.replace("px",""));
        } else {
            var s = 12;
        }
        if(s<fontmax) {
            s += 5;
        }
        p[i].style.fontSize = s+"px"
    }
}


function decreaseFontSize() {
    var p = document.getElementsByTagName('p');
    for(i=0;i<p.length;i++) {
        if(p[i].style.fontSize) {
            var s = parseInt(p[i].style.fontSize.replace("px",""));
        } else {
            var s = 12;
        }
        if(s>fontmin) {
            s -= 5;
        }
        p[i].style.fontSize = s+"px"
    }
}

