
var testObj0;
var alertStatusObj;
var alertTimer;
var alertFailedTimer;
var alertdone;
var replyrate=10000;  //10 sec
var fontmin=12;
var fontmax=24;
var sentbyajax=false;
var cartSendingCount=0;
var cartSentCount=0;
var cartSendBusy=false;
var sendingQ=new Array();
var unBusyCartTimer=2000;
var cartSendTimer=1000;
var lastCartQuantity=-1;
var lastCartPsu=-1;

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
    ajaxGet(alertStatusObj,"/test",testcallback1);
}

function checkdisplay(){
      width=getViewWidth();
      height=getViewHeight();
      //alert(ajaxstatus+ajax);
      alertdone=0;
      alertFailedTimer=setTimeout("noajax()",4000);  //4 sec time out
      alertTimer=setTimeout("checkdisplay()",replyrate);  //then replyrate intervals
      replyrate=replyrate*1.5; //increase each time
      if(replyrate>3600000)replyrate=3600000; //reply every hour
      //@todo add logging or setting of screen parameters to back end
      //ajaxGet(alertStatusObj,"/page/ajaxcheck.sys?verify=display&w="+width+"&h="+height,alertstatusreply);
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
function sendNextCart(){
    sendurl=sendingQ[cartSentCount];
    ajaxGet(cartStatusObj,sendurl,cartstatusreply);
    sentbyajax=true;
    cartSendBusy=true;
    cartSentCount++;
}

function unBusyCart(){
    cartSendBusy=false;
   cartSendingCount=0;
   cartSentCount=0;
}


function ajaxCreateRequestObject(){
    if (window.XMLHttpRequest) {
       var requestObj = new XMLHttpRequest();

    } else if (window.ActiveXObject) {
       var requestObj  = new ActiveXObject("Microsoft.XMLHTTP");
    }
    return requestObj;
}



function ajaxGet(requestObj,url,callback){
        requestObj.open("GET", url, true);
        requestObj.onreadystatechange = callback;
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
  if((reply=ajaxReply(alertStatusObj))!="**wait**"){
      alert(reply);
  }
}

function testcallback1(){
    reply=ajaxReply(alertStatusObj);
   document.getElementById('monitorMessage').className= "alertText";
     document.getElementById('monitorMessage').innerHTML= reply;
 
  if((reply=ajaxReply(alertStatusObj))!="**wait**"){
      alert(reply);
  }
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

function additemtocart(add,num,psu,quantityid,cartblockid,title){
  var quantity = document.getElementById(quantityid).value;
  quantity = quantity.replace(/\s+/g, '');  //remove spaces

  enablelink=true; //by default the object will fire
                    //we should set it to false if no error

  if(lastCartQuantity==quantity && lastCartPsu==psu){
      enablelink=false;
      //ignore duplicate requests ie firing of add and button
  }else{
        if(add!='c'){
              if(quantity=='') quantity=1;
              if(quantity==0)  quantity=-1;
        }else{
             if(quantity=='') quantity=0;
        }




        if(quantity>=0){
            sentbyajax=false;
        //alert(quantity+" of "+num+" psu "+psu);
            document.getElementById(quantityid).value='';

            if(ajax){
              var sendurl = "/cart/cart.sys?change="+add+"&q="+quantity+"&psu="+psu+"&upid="+cartblockid;

              if(cartSendBusy){
                //sendingQ.push(sendurl);
                //cartSendingcount++;
                //sendingQ[cartSendingCount]=sendurl;
                //cartSendingCount++;
                alert("Your PC or Internet connection is busy and can not keep up with you!.\n\n Please press OK and wait for the page to update!\n\nWe also recommend that you check that all the items have been added to your cart");

              }else{
               // document.getElementById(cartblockid).innerHTML="<p>Sending to Cart</p><p>Please wait</p><p>If image does not refresh within a few seconds<br />there may be a problem reaching the web site<br /> <a href='?coms=loss'>click here and check items have been added</a> </p>";
                cartSendBusy=true;
                sentbyajax=true;
                ajaxGet(cartStatusObj,sendurl,cartstatusreply);
                enablelink=false;
                lastCartQuantity=document.getElementById(quantityid).value;
                lastCartPsu=psu;
                if(add=='c'){
                  message=title+":<br />Changing number in cart to "+ quantity;
                }else{
                  message=quantity+" x "+title+"<br /> are being placed in your cart";
                }

                document.getElementById('cartStatus').innerHTML="<div id='leftcart'></div><div id='rightcart'><h3>"+message+"</h3></div>";
                //win3 = window.open("", message, "width=320,height=210,scrollbars=yes");
                //win3 = window.open("", "Window3", "width=320,height=210,scrollbars=no");
               // win3.document.writeln(message);
                //win3.focus();
              //  if(!confirm(message)){
              //      alert("To remove or adjust the quantity:\n\nChange the number in the quantity box and press the 'change' button.\n\nOR\n\nUse the 'add 1' or insert 'remove 1' links next to the shopping cart icon");

              //  }
              }
            }
        }else{
          if(add!='c'){
             alert("Please enter a number greater than zero");
          }else{
             alert("Please enter the number of items you require ie a number greater than zero");
          }

           enablelink=false;
        }
  }
  return enablelink;
}



function cartstatusreply(){
  xmlstatusreply(cartStatusObj);
}

function cartStatusScrollReply(){
   xmlstatusreply(cartStatusScrollObj);
}

function addtocartsubmit(){
  var ok=true;
  if(sentbyajax){
     ok=false;
  }
  sentbyajax=false;
  return ok;
}

function catList(action,page){
  var newpage=true;
  document.getElementById('cartStatus').innerHTML="<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;please wait";
  if(ajax && page!='refresh'){
          ajaxGet(cartStatusScrollObj,"/topbanner/topbanner_x_cart_scroll_summary.sys?act="+action,cartStatusScrollReply);
          newpage=false;
  }
  return newpage;
};
