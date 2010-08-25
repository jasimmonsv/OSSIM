/*
 * Bindows Gauges Library
 * http://www.bindows.net/
 * Copyright (c) 2003-2007 MB Technologies
 *
 * Bindows(TM) and the Bindows Gauges Library belong to MB Technologies (Georgia, USA). All rights reserved.
 */
var _biInPrototype=false;function _biExtend(fConstr,fSuperConstr,sName){_biInPrototype=true;var p=fConstr.prototype=new fSuperConstr;if(sName){p._className=sName;}
p.constructor=fConstr;_biInPrototype=false;return p;}
Object.isEmpty=function(o)
{for(var _ in o)
return false;return true;};Object.getKeys=function(o){var r=[];for(var i in o){r.push(i);}
return r};Object.getValues=function(o){var r=[];for(var i in o){r.push(o[i]);}
return r;};if(!Array.prototype.indexOf){Array.prototype.indexOf=function(obj,fromIndex){if(fromIndex==null){fromIndex=0;}else if(fromIndex<0){fromIndex=Math.max(0,this.length+fromIndex);}
for(var i=fromIndex;i<this.length;i++){if(this[i]===obj)
return i;}
return-1;};}
if(!Array.prototype.lastIndexOf){Array.prototype.lastIndexOf=function(obj,fromIndex){if(fromIndex==null){fromIndex=this.length-1;}else if(fromIndex<0){fromIndex=Math.max(0,this.length+fromIndex);}
for(var i=fromIndex;i>=0;i--){if(this[i]===obj)
return i;}
return-1;};}
Array.prototype.contains=function(o){return this.indexOf(o)!= -1;};Array.prototype.copy=function(o){return this.concat();};Array.prototype.insertAt=function(o,i){this.splice(i,0,o);};Array.prototype.insertBefore=function(o,o2){var i=this.indexOf(o2);if(i== -1)
this.push(o);else this.splice(i,0,o);};Array.prototype.removeAt=function(i){this.splice(i,1);};Array.prototype.remove=function(o){var i=this.indexOf(o);if(i!= -1)
this.splice(i,1);};if(!Array.prototype.forEach){Array.prototype.forEach=function(f,obj){var l=this.length;for(var i=0;i<l;i++){f.call(obj,this[i],i,this);}};}
if(!Array.prototype.filter){Array.prototype.filter=function(f,obj){var l=this.length;var res=[];for(var i=0;i<l;i++){if(f.call(obj,this[i],i,this)){res.push(this[i]);}}
return res;};}
if(!Array.prototype.map){Array.prototype.map=function(f,obj){var l=this.length;var res=[];for(var i=0;i<l;i++){res.push(f.call(obj,this[i],i,this));}
return res;};}
if(!Array.prototype.some){Array.prototype.some=function(f,obj){var l=this.length;for(var i=0;i<l;i++){if(f.call(obj,this[i],i,this)){return true;}}
return false;};}
if(!Array.prototype.every){Array.prototype.every=function(f,obj){var l=this.length;for(var i=0;i<l;i++){if(!f.call(obj,this[i],i,this)){return false;}}
return true;};}
String._rExpTrim= /(^\s+)|\s+$/g;String.prototype.trim=function(){return this.replace(String._rExpTrim,"");};String.prototype.capitalize=function(){return this.charAt(0).toUpperCase()+this.substr(1);};String.prototype.startsWith=function(s){return this.substring(0,s.length)==s;};String.prototype.endsWith=function(s){return this.substring(this.length-s.length,this.length)==s;};String.EMPTY="";String.BOOLEAN_TRUE="true";String.BOOLEAN_FALSE="false";Function.READ=1;Function.WRITE=2;Function.READ_WRITE=3;Function.EMPTY=function(){};Function.prototype.addProperty=function(sName,nReadWrite){var p=this.prototype;nReadWrite=nReadWrite||Function.READ_WRITE;var capitalized=sName.capitalize();sName="_"+sName;if(nReadWrite&Function.READ)
{p["get"+capitalized]=function()
{return this[sName];};}
if(nReadWrite&Function.WRITE)
{p["set"+capitalized]=function(v)
{this[sName]=v;};}};function BiObject(){}
_p=_biExtend(BiObject,Object,"BiObject");_p._disposed=false;_p._id=null;BiObject.TYPE_FUNCTION="function";BiObject.TYPE_OBJECT="object";BiObject.TYPE_STRING="string";BiObject._hashCodeCounter=1;BiObject.toHashCode=function(o){if(o.hasOwnProperty("_hashCode"))
return o._hashCode;return o._hashCode="_"+(BiObject._hashCodeCounter++).toString(32);};BiObject.prototype.getDisposed=function(){return this._disposed;};BiObject.prototype.getId=function(){return this._id;};BiObject.prototype.setId=function(v){this._id=v;};BiObject.prototype.getUserData=function(){return this._userData;};BiObject.prototype.setUserData=function(v){this._userData=v;};_p.toHashCode=function(){return BiObject.toHashCode(this);};_p.dispose=function(){this._disposed=true;delete this._userData;delete this._id;this.dispose=Function.EMPTY;};_p.disposeFields=function(fieldNames){var fields=fieldNames instanceof Array?fieldNames:arguments;var n,o,p;for(var i=0;i<fields.length;i++){n=fields[i];if(this.hasOwnProperty(n)){o=this[n];if(o!=null){if(typeof o.dispose==BiObject.TYPE_FUNCTION){o.dispose();}else if(o instanceof Array){for(var j=o.length-1;j>=0;j--){p=o[j];if(p&&typeof p.dispose==BiObject.TYPE_FUNCTION){p.dispose();}}}}
delete this[n];}}};_p.toString=function(){if(this._className)
return"[object "+this._className+"]";return"[object Object]";};_p.getProperty=function(sPropertyName){var getterName="get"+sPropertyName.capitalize();if(typeof this[getterName]==BiObject.TYPE_FUNCTION)
return this[getterName]();throw new Error("No such property, "+sPropertyName);};_p.setProperty=function(sPropertyName,oValue){var setterName="set"+sPropertyName.capitalize();if(typeof this[setterName]==BiObject.TYPE_FUNCTION)
this[setterName](oValue);else throw new Error("No such property, "+sPropertyName);};_p.setProperties=function(oProperties){for(var p in oProperties){this.setProperty(p,oProperties[p]);}};_p.setAttribute=function(sName,sValue,oParser)
{var v,vv;if(sValue==String.BOOLEAN_TRUE)
v=true;else if(sValue==String.BOOLEAN_FALSE)
v=false;else if((vv=parseFloat(sValue))==sValue)
v=vv;else v=sValue;this.setProperty(sName,v);};_p.getAttribute=function(sName)
{return String(this.getProperty(sName));};_p.addXmlNode=function(oNode,oParser)
{if(oNode.nodeType==1)
oParser.fromNode(oNode);};function _ClassStub(){}
_p=_ClassStub.prototype;_p.addBundle=_p.attachToWindow=_p.dispose=_p.getAppearanceTag=Function.EMPTY;BiAdf=BiFocusManager=BiEventManager=BiStringBundle=BiThemeManager=BiXmlLoader=_ClassStub;if(typeof BiObject=="undefined")
BiObject=new Function;function BiBrowserCheck(){throw new Error("Cannot create instance of BiBrowserCheck.");};_biExtend(BiBrowserCheck,BiObject,"BiBrowserCheck");BiBrowserCheck.ie= /msie/i.test(navigator.userAgent);BiBrowserCheck.moz=navigator.product=="Gecko";BiBrowserCheck.platform=navigator.platform;BiBrowserCheck.hta=BiBrowserCheck.ie&&!window.external;if(!(BiBrowserCheck.moz?/rv\:(.+)[\);]/:/MSIE\s+(.+)[\);]/).test(navigator.userAgent))
throw new Error("Unable to detect Browser version.");BiBrowserCheck.version=RegExp.$1;BiBrowserCheck.versionNumber=parseFloat(BiBrowserCheck.version);BiBrowserCheck.supported=(BiBrowserCheck.ie&&BiBrowserCheck.versionNumber>=5.5)||(BiBrowserCheck.moz&&BiBrowserCheck.versionNumber>=1.4);function BiUri(sBase,sRel)
{if(_biInPrototype)return;this._params={};if(sBase)
{this.setHref(sBase);if(sRel)
this._setRelative(sRel)}}
_p=_biExtend(BiUri,BiObject,"BiUri");_p._scheme="";_p._userInfo="";_p._port="";_p._host="";_p._path="";_p._dirPath="";_p._fragment="";_p._query="";_p._hrefCache=null;_p._generic=true;BiUri.prototype.getScheme=function(){return this._scheme;};BiUri.prototype.getPath=function(){return this._path;};BiUri.prototype.getDirPath=function(){return this._dirPath;};BiUri.prototype.getHost=function(){return this._host;};BiUri.prototype.getPort=function(){return this._port;};BiUri.prototype.getFragment=function(){return this._fragment;};BiUri.prototype.getUserInfo=function(){return this._userInfo;};BiUri.regExps={scheme:/^([^:]+)\:.+$/,user:/^([^@\/]+)@.+$/,host:/^([^:\/\?\#]+).*$/,port:/^:(\d+)/,path:/^([^\?#]*)/,dirPath:/^(.*\/)[^\/]*$/,fragment:/^[^#]*#(.*)$/,absUri:/^\w(\w|\d|\+|\-|\.)*:/i};_p.toString=function()
{return this.getHref();};_p.setHref=function(s)
{this._hrefCache=null;s=String(s);this._scheme="";this._userInfo="";this._host="";this._port=null;this._path="";this._dirPath="";this._query="";this._fragment="";this._params={};var err=new Error("Not a well formatted URI");var ok=BiUri.regExps.scheme.test(s);if(!ok)throw err;this._scheme=RegExp.$1;this._generic=s.substr(this._scheme.length,3)=="://";if(this._generic)
s=s.substring(this._scheme.length+3);else s=s.substring(this._scheme.length+1);if(this._generic||this._scheme=="mailto"||this._scheme=="news")
{ok=BiUri.regExps.user.test(s);if(ok)
{this._userInfo=RegExp.$1;s=s.substring(this._userInfo.length+1);}
if(this._scheme!="file"||s.charAt(0)!="/")
{ok=BiUri.regExps.host.test(s);if(!ok)
throw err;this._host=RegExp.$1;s=s.substring(this._host.length);}
ok=BiUri.regExps.port.test(s);if(ok)
{this._port=Number(RegExp.$1);s=s.substring(RegExp.$1.length+1);}}
this._parsePathAndRest(s);};_p._parsePathAndRest=function(s)
{var err=new Error("Not a well formatted URI");var i;var ok=BiUri.regExps.path.test(s);if(!ok)
throw err;this._path=RegExp.$1;s=s.substring(this._path.length);if(this._path==""&&(this._scheme=="file"||this._scheme=="http"||this._scheme=="https"||this._scheme=="ftp"))
{this._path="/";}
var segments=this._path.split("/");var sb=[];var j=0;for(i=0;i<segments.length;i++)
{if(segments[i]==".")
continue;if(segments[i]=="..")
{j--;delete sb[j];sb.length=j
continue;}
sb[j++]=segments[i];}
this._path=sb.join("/");if(this._path.length>0)
{ok=BiUri.regExps.dirPath.test(this._path);if(!ok)
throw err;this._dirPath=RegExp.$1;}
ok=BiUri.regExps.fragment.test(s);if(ok)
{this._fragment=RegExp.$1;s=s.substring(0,s.length-this._fragment.length-1);this._fragment="#"+this._fragment.replace("#","%23");}
this._query=s;s=s.substring(1);if(this._query!="")
{var pairs=s.split(/\;|\&/);var parts,name,value;for(i=0;i<pairs.length;i++)
{parts=pairs[i].split("=");try{name=decodeURIComponent(parts[0]);}
catch(e){name=parts[0];}
if(parts.length==2)
{try{value=decodeURIComponent(parts[1]);}
catch(e){value=parts[1];}}
else value=null;if(name in this._params)
this._params[name].push(value);else this._params[name]=[value];}}};_p._setRelative=function(s)
{this._hrefCache=null;s=String(s);var isAbsolute=BiUri.regExps.absUri.test(s);if(isAbsolute)
{this.setHref(s);return;}
var dirPath=this._dirPath;this._path="";this._dirPath="";this._query="";this._fragment="";this._params={};if(s.charAt(0)=="/")
{this._parsePathAndRest(s)}
else this._parsePathAndRest(dirPath+s);};_p.getHref=function()
{if(this._hrefCache!=null)
return this._hrefCache;var s=this._scheme+(this._generic?"://":":")+this._userInfo+(this._userInfo==""?"":"@")+this._host+(this._port!=null?":"+this._port:"")+this._path;return this._hrefCache=s+this.getQuery()+this._fragment;};_p.getParam=function(sName)
{if(sName in this._params)
return this._params[sName][this._params[sName].length-1];return undefined;};_p.setParam=function(sName,sValue)
{this._hrefCache=null;return this._params[sName]=[String(sValue)];};_p.removeParam=function(sName)
{this._hrefCache=null;delete this._params[sName];};_p.hasParam=function(sName)
{return sName in this._params;};_p.getParams=function(sName)
{if(sName in this._params)
return this._params[sName].concat();return[];};_p.addParam=function(sName,sValue)
{this._hrefCache=null;var v=sValue==null?null:String(sValue);if(sName in this._params)
this._params[sName].push(v);else this._params[sName]=[v];};_p.getQuery=function()
{var sb=[];var sb2,sb3,v;for(var name in this._params)
{sb2=[];for(var i=0;i<this._params[name].length;i++)
{sb3=[];v=this._params[name][i];if(v==null)
sb2.push(encodeURIComponent(name));else {sb3.push(encodeURIComponent(name),"=",encodeURIComponent(v));sb2.push(sb3.join(""));}}
sb.push(sb2.join("&"));}
return(sb.length>0?"?"+sb.join("&"):"");};function BiEvent(sType)
{if(_biInPrototype)return;BiObject.call(this);this._type=sType;}
_p=_biExtend(BiEvent,BiObject,"BiEvent");_p._bubbles=false;_p._propagationStopped=true;_p._defaultPrevented=false;BiEvent.prototype.getType=function(){return this._type;};BiEvent.prototype.getTarget=function(){return this._target;};BiEvent.prototype.getCurrentTarget=function(){return this._currentTarget;};BiEvent.prototype.getBubbles=function(){return this._bubbles;};_p.stopPropagation=function()
{this._propagationStopped=true;};BiEvent.prototype.getPropagationStopped=function(){return this._propagationStopped;};_p.preventDefault=function()
{this._defaultPrevented=true;};BiEvent.prototype.getDefaultPrevented=function(){return this._defaultPrevented;};_p.dispose=function()
{BiObject.prototype.dispose.call(this);delete this._target;delete this._currentTarget;delete this._bubbles;delete this._propagationStopped;delete this._defaultPrevented;};_p.getDefaultPrevented=function()
{return this._defaultPrevented;};function BiMouseEvent(){};function BiKeyboardEvent(){};function BiEventTarget(){if(_biInPrototype)return;BiObject.call(this);this._listeners={};this._listenersCount=0;}
_p=_biExtend(BiEventTarget,BiObject,"BiEventTarget");_p.addEventListener=function(sType,fHandler,oObject){if(typeof fHandler!=BiObject.TYPE_FUNCTION)
throw new Error(this+" addEventListener: "+fHandler+" is not a function");var ls=this._listeners[sType];if(!ls)ls=this._listeners[sType]={};var key=BiObject.toHashCode(fHandler)+(oObject?BiObject.toHashCode(oObject):String.EMPTY);if(!(key in ls)){this._listenersCount++;}
ls[key]={handler:fHandler,object:oObject||this};};_p.removeEventListener=function(sType,fHandler,oObject){if(this._disposed||!(sType in this._listeners))
return;var key=BiObject.toHashCode(fHandler)+(oObject?BiObject.toHashCode(oObject):String.EMPTY);if(key in this._listeners[sType]){this._listenersCount--;}
delete this._listeners[sType][key];if(Object.isEmpty(this._listeners[sType])){delete this._listeners[sType];}};_p.dispatchEvent=function(e){if(this._disposed)
return;if(typeof e==BiObject.TYPE_STRING){e=new BiEvent(e);}
e._target=this;this._dispatchEvent(e);delete e._target;return!e._defaultPrevented;};_p._dispatchEvent=function(e){e._currentTarget=this;if(this._listenersCount>0&&(!(e instanceof BiMouseEvent)&&!(e instanceof BiKeyboardEvent)||this.getIsEnabled()))
{var fs=this._listeners[e.getType()];if(fs)
{for(var hc in fs)
{var ho=fs[hc];ho.handler.call(ho.object,e);}}}
if(e._bubbles&&!e._propagationStopped&&this._parent&&!this._parent._disposed)
{this._parent._dispatchEvent(e);}
delete e._currentTarget;};_p.setAttribute=function(sName,sValue,oParser){if(sName.substring(0,2)=="on")
{var type=sName.substring(2);this.addEventListener(type,new Function("event",sValue),oParser);}
else BiObject.prototype.setAttribute.call(this,sName,sValue,oParser);};_p.dispose=function()
{if(this._disposed)return;BiObject.prototype.dispose.call(this);for(var t in this._listeners)
delete this._listeners[t];delete this._listeners;delete this._listenersCount;};_p.hasListeners=function(sType){return this._listenersCount>0&&(sType==null||sType in this._listeners);};function BiXmlHttp(){if(_biInPrototype)return;if(typeof XMLHttpRequest!="undefined"){return new XMLHttpRequest;}
throw new Error("Your browser does not support XML HTTP Requests");}
BiXmlHttp.prototype=new Object;BiXmlHttp.create=function(){return new BiXmlHttp();};function BiXmlDocument(){if(_biInPrototype)return;if(document.implementation&&document.implementation.createDocument){var doc=document.implementation.createDocument("","",null);doc.addEventListener("load",function(e){this.readyState=4;},false);doc.readyState=4;return doc;}else if(window.ActiveXObject){return new ActiveXObject(BiXmlDocument._getActiveXName());}
throw new Error("Your browser does not support creating DOM documents at runtime");}
BiXmlDocument.prototype=new Object;BiXmlDocument.create=function(){return new BiXmlDocument();};BiXmlDocument.getNamespaces=function(oNode){if(oNode.nodeType!=9){oNode=oNode.ownerDocument;}
var res={};if(BiBrowserCheck.ie)
{var s=oNode.getProperty("SelectionNamespaces");s.replace(/(^|\s+)xmlns\:([^=]+)=(\"[^\"]*\")|(\'[^\']*\')($|\s+)/g,function(a0,a1,a2,a3,a4){var urn=a3||a4;urn=urn.substring(1,urn.length-1);res[a2]=urn;});}
return res;};BiXmlDocument.setNamespaces=function(oNode,oNamespaces){if(oNode.nodeType!=9){oNode=oNode.ownerDocument;}
var sb=[];for(i in oNamespaces){sb.push("xmlns:",i,"=\"",oNamespaces[i],"\" ");}
oNode.setProperty("SelectionNamespaces",sb.join(""));};BiXmlDocument.addNamespaces=function(oNode,oNamespaces){var current=BiXmlDocument.getNamespaces(oNode);for(var i in oNamespaces){current[i]=oNamespaces[i];}
BiXmlDocument.setNamespaces(oNode,current);};BiXmlDocument.removeNamespaces=function(oNode,oNamespaces){var current=BiXmlDocument.getNamespaces(oNode);for(var i in oNamespaces){delete current[i];}
BiXmlDocument.setNamespaces(oNamespaces,current);};(function(){function f(type){var prefix=["MSXML2.","Microsoft.","MSXML."];var suffix=[".6.0",".4.0",".3.0",""];var o;for(var i=0;i<prefix.length;i++){for(var j=0;j<suffix.length;j++){try{o=new ActiveXObject(prefix[i]+type+suffix[j]);return prefix[i]+type+suffix[j];}catch(ex){};}}
throw new Error("Could not find an installed XML parser");};BiXmlDocument._getActiveXName=function(){var s=f("DOMDocument");this._getActiveXName=function(){return s;};return s;};BiXmlHttp._getActiveXName=function(){var s=f("XMLHTTP");this._getActiveXName=function(){return s;};return s;};})();if(typeof ActiveXObject=="undefined"&&typeof XMLHttpRequest!="undefined"&&!XMLHttpRequest.____ALREADY_LOADED____){(function(){var _xmlDocPrototype=XMLDocument.prototype;_xmlDocPrototype.__proto__={__proto__:_xmlDocPrototype.__proto__};var _p=_xmlDocPrototype.__proto__;_p.createNode=function(aType,aName,aNamespace){switch(aType){case 1:if(aNamespace&&aNamespace!="")
return this.createElementNS(aNamespace,aName);else return this.createElement(aName);case 2:if(aNamespace&&aNamespace!="")
return this.createAttributeNS(aNamespace,aName);else return this.createAttribute(aName);case 3:default:return this.createTextNode("");}};_p.__realLoad=_xmlDocPrototype.load;_p.load=function(sUri){this.readyState=0;this.__realLoad(sUri);};_p.loadXML=function(s){var doc2=(new DOMParser).parseFromString(s,"text/xml");while(this.hasChildNodes())
this.removeChild(this.lastChild);var cs=doc2.childNodes;var l=cs.length;for(var i=0;i<l;i++)
this.appendChild(this.importNode(cs[i],true));};_p.setProperty=function(sName,sValue){if(sName=="SelectionNamespaces"){this._selectionNamespaces={};var parts=sValue.split(/\s+/);var re= /^xmlns\:([^=]+)\=((\"([^\"]*)\")|(\'([^\']*)\'))$/;for(var i=0;i<parts.length;i++){re.test(parts[i]);this._selectionNamespaces[RegExp.$1]=RegExp.$4||RegExp.$6;}}};_p.__defineSetter__("onreadystatechange",function(f){if(this._onreadystatechange)
this.removeEventListener("load",this._onreadystatechange,false);this._onreadystatechange=f;if(f)
this.addEventListener("load",f,false);return f;});_p.__defineGetter__("onreadystatechange",function(){return this._onreadystatechange;});BiXmlDocument._mozHasParseError=function(oDoc){return!oDoc.documentElement||oDoc.documentElement.localName=="parsererror"&&oDoc.documentElement.getAttribute("xmlns")=="http://www.mozilla.org/newlayout/xml/parsererror.xml";};_p.__defineGetter__("parseError",function(){var hasError=BiXmlDocument._mozHasParseError(this);var res={errorCode:0,filepos:0,line:0,linepos:0,reason:"",srcText:"",url:""};if(hasError){res.errorCode= -1;try{res.srcText=this.getElementsByTagName("sourcetext")[0].firstChild.data;res.srcText=res.srcText.replace(/\n\-\^$/,"");}catch(ex){res.srcText="";}
try{var s=this.documentElement.firstChild.data;var re= /XML Parsing Error\:(.+)\nLocation\:(.+)\nLine Number(\d+)\,Column(\d+)/;var a=re.exec(s);res.reason=a[1];res.url=a[2];res.line=a[3];res.linepos=a[4];}catch(ex){res.reason="Unknown";}}
return res;});var _nodePrototype=Node.prototype;_nodePrototype.__proto__={__proto__:_nodePrototype.__proto__};_p=_nodePrototype.__proto__;_p.__defineGetter__("xml",function(){return(new XMLSerializer).serializeToString(this);});_p.__defineGetter__("baseName",function(){var lParts=this.nodeName.split(":");return lParts[lParts.length-1];});_p.__defineGetter__("text",function(){var cs=this.childNodes;var l=cs.length;var sb=new Array(l);for(var i=0;i<l;i++)
sb[i]=cs[i].text;return sb.join("");});_p.selectNodes=function(sExpr){var doc=this.nodeType==9?this:this.ownerDocument;var nsRes=doc.createNSResolver(this.nodeType==9?this.documentElement:this);var nsRes2;if(doc._selectionNamespaces){nsRes2=function(s){if(s in doc._selectionNamespaces)
return doc._selectionNamespaces[s];return nsRes.lookupNamespaceURI(s);};}else{nsRes2=nsRes;}
var xpRes=doc.evaluate(sExpr,this,nsRes2,5,null);var res=[];var item;while((item=xpRes.iterateNext()))
res.push(item);return res;};_p.selectSingleNode=function(sExpr){var doc=this.nodeType==9?this:this.ownerDocument;var nsRes;try{nsRes=doc.createNSResolver(this.nodeType==9?this.documentElement:this);}
catch(e){nsRes=null;}
var nsRes2;if(doc._selectionNamespaces){nsRes2=function(s){if(s in doc._selectionNamespaces)
return doc._selectionNamespaces[s];return nsRes.lookupNamespaceURI(s);};}else{nsRes2=nsRes;}
var xpRes=doc.evaluate(sExpr,this,nsRes2,9,null);return xpRes.singleNodeValue;};_p.transformNode=function(oXsltNode){var doc=this.nodeType==9?this:this.ownerDocument;var processor=new XSLTProcessor();processor.importStylesheet(oXsltNode);var df=processor.transformToFragment(this,doc);return df.xml;};_p.transformNodeToObject=function(oXsltNode,oOutputDocument){var doc=this.nodeType==9?this:this.ownerDocument;var outDoc=oOutputDocument.nodeType==9?oOutputDocument:oOutputDocument.ownerDocument;var processor=new XSLTProcessor();processor.importStylesheet(oXsltNode);var df=processor.transformToFragment(this,doc);while(oOutputDocument.hasChildNodes())
oOutputDocument.removeChild(oOutputDocument.lastChild);var cs=df.childNodes;var l=cs.length;for(var i=0;i<l;i++)
oOutputDocument.appendChild(outDoc.importNode(cs[i],true));};var _attrPrototype=Attr.prototype;_attrPrototype.__proto__={__proto__:_attrPrototype.__proto__};_p=_attrPrototype.__proto__;_p.__defineGetter__("xml",function(){var nv=(new XMLSerializer).serializeToString(this);return this.nodeName+"=\""+nv.replace(/\"/g,"&quot;")+"\"";});var _textPrototype=Text.prototype;_textPrototype.__proto__={__proto__:_textPrototype.__proto__};_p=_textPrototype.__proto__;_p.__defineGetter__("text",function(){return this.nodeValue;});})();}
if(typeof ActiveXObject!="undefined"){if(window.XMLHttpRequest){}
else{XMLHttpRequest=function(){return new ActiveXObject(BiXmlHttp._getActiveXName());};XMLHttpRequest.____ALREADY_LOADED____=true;}
if(!window.DOMParser){DOMParser=function(){};DOMParser.prototype.parseFromString=function(s,mime){var doc=new BiXmlDocument;doc.loadXML(s);return doc;};}
if(!window.XMLSerializer){XMLSerializer=function(){};XMLSerializer.prototype.serializeToString=function(n){return n.xml;};}}
function BiLauncher(sRootPath)
{if(_biInPrototype)return;if(sRootPath)
this.setRootPath(sRootPath);this._arguments=[];}
_p=_biExtend(BiLauncher,Object,"BiLauncher");_p._reuseWindow=true;_p._newWindow=true;_p._errorMessage="";_p._accessibilityMode=false;_p._focusOnLoad=true;BiLauncher.MISSING_ADF_ARGUMENT="Missing ADF argument";BiLauncher.ADF_ARGUMENT_PARSE_ERROR="The ADF argument cannot be parsed";BiLauncher.IE_ERROR_PLATFORM="Bindows requires Internet Explorer for Windows";BiLauncher.IE_ERROR_VERSION="Bindows requires Internet Explorer 5.5 or later";BiLauncher.GECKO_ERROR_VERSION="Bindows requires Mozilla (Gecko) 1.4 or later";BiLauncher.NOT_SUPPORTED_ERROR="Bindows requires Internet Explorer 5.5+ or Mozilla 1.4+";BiLauncher.FILE_NOT_FOUND="File not found";BiLauncher.POPUP_BLOCKER_QUESTION="Failed to open window. Are you using a popup blocker?";_p.getReuseWindow=function()
{return this._reuseWindow;};_p.setReuseWindow=function(b)
{this._reuseWindow=b;};_p.getNewWindow=function()
{return this._newWindow;};_p.setNewWindow=function(b)
{this._newWindow=b;};_p.getWindow=function()
{return this._window||null;};_p.getRootPath=function()
{return this._rootPath;};_p.setRootPath=function(s)
{s=String(s);if(s.charAt(s.length-1)!="/")
s+="/";this._rootPath=s;};_p.getAdfPath=function()
{return this._adfPath;};_p.setAdfPath=function(s)
{if(s==null||s=="")
{this._errorMessage=BiLauncher.MISSING_ADF_ARGUMENT;return;}
s=String(s);var re= /([\w]+)(?:\.[\w]+)?(?:$|\?)/;if(re.test(s))
{this._adfName=RegExp.$1;this._adfPath=s;}
else {this._errorMessage=BiLauncher.ADF_ARGUMENT_PARSE_ERROR;}};_p.getAdfName=function()
{return this._adfName;};_p.setAdfName=function(s)
{this._adfName=s;};_p.getArguments=function()
{return this._arguments;};_p.setArguments=function(a)
{this._arguments=[];for(var i=0;i<a.length;i++)
this._arguments.push(String(a[i]));};_p.getTarget=function()
{return this._target;};_p.setTarget=function(s)
{this._target=s;};_p.setAccessibilityMode=function(b)
{this._accessibilityMode=b;};_p.getAccessibilityMode=function()
{return this._accessibilityMode;};_p.getFocusOnLoad=function()
{return this._focusOnLoad;};_p.setFocusOnLoad=function(b)
{this._focusOnLoad=b;};_p.getSupported=function()
{var p;if(BiBrowserCheck.ie)
{p=String(BiBrowserCheck.platform).toLowerCase();if(p!="win32"&&p!="win64")
{this._errorMessage=BiLauncher.IE_ERROR_PLATFORM;return false;}
if(BiBrowserCheck.versionNumber<5.5)
{this._errorMessage=BiLauncher.IE_ERROR_VERSION;return false;}
return true;}
else if(BiBrowserCheck.moz)
{if(BiBrowserCheck.versionNumber<1.4)
{this._errorMessage=BiLauncher.GECKO_ERROR_VERSION;return false;}
return true;}
this._errorMessage=BiLauncher.NOT_SUPPORTED_ERROR;return false;};_p.getErrorMessage=function()
{return this._errorMessage;};_p.getHasError=function()
{return this._errorMessage!="";};_p.launch=function(sAdfPath,oArgs)
{var left,right,top,bottom,width,height,centered,resizable,fullScreen;var adfPath,adfName,args;if(!this.getSupported())
return false;if(sAdfPath)
this.setAdfPath(sAdfPath);if(this.getHasError())
return false;var bUseCurrentWindow=!this.getNewWindow();var sRootPath=this.getRootPath();var sAdfRelPath=this.getAdfPath();if(arguments.length>1)
{args=[];for(var i=1;i<arguments.length;i++)
args.push(arguments[i]);this.setArguments(args);}
adfName=this.getAdfName();args=this.getArguments();if(/(^http\:)|(^https\:)|(^file\:)|(^\/)/.test(sAdfRelPath))
{adfPath=sAdfRelPath;}
else {var curPath=document.location.href;var slashIndex=curPath.lastIndexOf("/");curPath=curPath.substring(0,slashIndex);adfPath=curPath+"/"+sAdfRelPath;}
var uri=sRootPath+"bimain.html?Adf="+encodeURIComponent(adfPath)+";AdfName="+adfName+(this._accessibilityMode?";accessibilityMode=true":"")+";Params="+args.length;for(i=0;i<args.length;i++)
{uri+=";Param"+i+"="+encodeURIComponent(args[i]);}
var xmlHttp=new BiXmlHttp;xmlHttp.open("GET",adfPath,false);try
{xmlHttp.send(null);}
catch(ex)
{this._errorMessage=BiLauncher.FILE_NOT_FOUND;return false;}
var fs= /^file\:/.test(adfPath);if(fs)
{var s=String(xmlHttp.responseText).replace(/<\?xml[^\?]*\?>/,"");xmlHttp.responseXML.loadXML(s);}
else if(xmlHttp.status!=200)
{this._errorMessage=xmlHttp.status+": "+xmlHttp.statusText;return false;}
if(xmlHttp.responseXML.parseError.errorCode!=0)
{this._errorMessage=xmlHttp.responseXML.parseError.reason;return false;}
var doc=xmlHttp.responseXML;var n=doc.selectSingleNode("/application/window | /Application/Window");left=BiLauncher._getAttr(n,"left","","x");right=BiLauncher._getAttr(n,"right","","x");top=BiLauncher._getAttr(n,"top","","y");bottom=BiLauncher._getAttr(n,"bottom","","y");width=BiLauncher._getAttr(n,"width","","x");height=BiLauncher._getAttr(n,"height","","y");centered=BiLauncher._getAttr(n,"centered","false")=="true";resizable=BiLauncher._getAttr(n,"resizable","true")!="false";fullScreen=BiLauncher._getAttr(n,"fullScreen","false")=="true";var sw=screen.width;var sh=screen.height;if(right!=""&&width!="")
left=sw-width-right;else if(left!=""&&right!="")
width=sw-left-right;if(bottom!=""&&height!="")
top=sh-height-bottom;else if(top!=""&&bottom!="")
height=sh-top-bottom;if(left==""&&right==""&&centered)
left=(sw-width)/2;if(top==""&&bottom==""&&centered)
top=(sh-height)/2;n=doc.selectSingleNode("/application/@focusOnLoad | /Application/@focusOnLoad");if(n)
this._focusOnLoad=n.text!="false";if(!bUseCurrentWindow)
{var windowName=this.getReuseWindow()?this._target||adfName:"";var w=window.open(BiBrowserCheck.moz?sRootPath+"blank.html":uri,windowName,"menubar=0,location=0,status=0,toolbar=0,scrollbars=1"+(left?",left="+left:"")+(top?",top="+top:"")+(width?",width="+(width-8):"")+(height?",height="+(height-32):"")+(fullScreen?",fullscreen=1":"")+(resizable?",resizable=1":""),false);if(!w)
{this._errorMessage=BiLauncher.POPUP_BLOCKER_QUESTION;return false;}
if(this._focusOnLoad)
w.focus();this._window=w;if(BiBrowserCheck.moz)
setTimeout(function(){w.document.location.href=uri;});}
else {document.location.href=uri;if(this._focusOnLoad)
window.focus();this._window=window;}
return true;};BiLauncher._toPixel=function(s,sAxis)
{if(String(s).indexOf("%")!= -1)
{var n=Number(s.replace(/\%/g,""));return n/100*(sAxis=="x"?screen.availWidth:screen.availHeight);}
return s;};BiLauncher._getAttr=function(el,name,def,tp)
{var res;if(!el||!el.getAttribute(name))
res=def;else res=el.getAttribute(name);if(tp)
return BiLauncher._toPixel(res,tp);return res;};function biExec(sRootPath,sAdfRelPath,bUseCurrentWindow)
{var args=[sAdfRelPath];for(var i=3;i<arguments.length;i++)
args.push(arguments[i]);var l=new BiLauncher(sRootPath);l.setAdfPath(sAdfRelPath);l.setNewWindow(!bUseCurrentWindow);var ok=l.launch.apply(l,args);if(!ok)
alert(l.getErrorMessage())
return ok;}
function BiHashTable(){if(_biInPrototype)return;BiObject.call(this);this._hash={};this._count=0;this._addCount=0;}
_p=_biExtend(BiHashTable,BiObject,"BiHashTable");_p.add=function(key,val){if(!this.hasKey(key)){this._hash[key]=val;this._count++;this._addCount++;}};_p.remove=function(key){if(this.hasKey(key)){delete this._hash[key];this._count--;if(BiBrowserCheck.ie&&this._addCount>this._count*4){this._recreate();}}};_p.clear=function(){this._hash={};this._count=0;this._addCount=0;};_p.item=function(key){if(this.hasKey(key)){return this._hash[key];}
return undefined;};_p.hasKey=function(key){return this._hash.hasOwnProperty(key);};_p.getKeys=function(){var res=[];for(var k in this._hash){if(this._hash.hasOwnProperty(k)){res.push(k);}}
return res;};_p.getValues=function(){var res=[];for(var k in this._hash){if(this._hash.hasOwnProperty(k)){res.push(this._hash[k]);}}
return res;};_p.isEmpty=function(){return this._count==0;};_p._recreate=function(){var tmp={};for(var k in this._hash){if(this._hash.hasOwnProperty(k)){tmp[k]=this._hash[k]}}
this._hash=tmp;this._addCount=this._count;};BiHashTable.prototype.getCount=function(){return this._count;};_p.dispose=function(){if(this._disposed)return;BiObject.prototype.dispose.call(this);delete this._hash;};function BiXmlResourceParser(){if(_biInPrototype)return;BiXmlLoader.call(this);this._componentsById=new BiHashTable;}
_p=_biExtend(BiXmlResourceParser,BiXmlLoader,"BiXmlResourceParser");_p._disposed=false;_p._autoNameMapping=false;BiXmlResourceParser.prototype.getAutoNameMapping=function(){return this._autoNameMapping;};BiXmlResourceParser.prototype.setAutoNameMapping=function(v){this._autoNameMapping=v;};_p._rootNode=null;BiXmlResourceParser.getClassFromUri=function(oUri){return BiXmlResourceParser.getClassFromDocument(BiXmlLoader.load(oUri));};BiXmlResourceParser.getClassFromDocument=function(oDoc){return BiXmlResourceParser.getClassFromNode(oDoc.documentElement);};BiXmlResourceParser.getClassFromNode=function(oNode){if(oNode==null||oNode.nodeType!=1)
return null;var tagName=oNode.localName||oNode.baseName;var constr=window["Bi"+tagName]||window[tagName];if(typeof constr=="function"){_biInPrototype=true;var p=new constr;_biInPrototype=false;var newConstr=function(){if(_biInPrototype)return;constr.apply(this,arguments);this._xmlResourceParser=new BiXmlResourceParser;this._xmlResourceParser.setRootNode(oNode);this._xmlResourceParser.processAttributes(this,oNode);this._xmlResourceParser.processChildNodes(this,oNode);if(typeof p.initialize=="function")
p.initialize.apply(this,arguments);};newConstr.prototype=p;p.dispose();p._disposed=false;p.dispose=function(){if(this.getDisposed())return;constr.prototype.dispose.call(this);this._xmlResourceParser.dispose();delete this._xmlResourceParser;};p.getComponentById=function(sId){return this._xmlResourceParser.getComponentById(sId);};p.getXmlResourceParser=function(){return this._xmlResourceParser;};p.initialize=p.initialize||Function.EMPTY;application.addEventListener("dispose",function(){newConstr=null;oNode=null;});return newConstr;}
throw new Error("BiXmlResourceParser getClassFromNode. Cannot create object from \""+oNode.tagName+"\"");};BiXmlResourceParser.prototype.setRootNode=function(v){this._rootNode=v;};_p.getRootNode=function(){if(this._rootNode){return this._rootNode;}else{if(this.getLoaded())
return this.getDocument();return null;}};_p.fromNode=function(oNode){if(oNode==null||oNode.nodeType!=1)
return null;var id=oNode.getAttribute("id");var c;if(id&&(c=this._componentsById.item(id))){if(c.getDisposed()){this._removeObject(c);}else{return c;}}
var tagName=oNode.localName||oNode.baseName;var o;var constr=window["Bi"+tagName]||window[tagName];if(typeof constr=="function"){o=new constr;this.processAttributes(o,oNode);this.processChildNodes(o,oNode);return o;}
throw new Error("BiXmlResourceParser fromNode. Cannot create object from \""+oNode.tagName+"\"");};_p._removeObject=function(o){var id=o.getId();this._componentsById.remove(id);if(this._autoNameMapping){try{delete window[id];}catch(ex){window[id]=null;}}};_p._addObject=function(o,id){this._componentsById.add(id,o);if(this._autoNameMapping){window[id]=o;}
var orgDispose=o.dispose;var oResParser=this;o.dispose=function(){if(oResParser)oResParser._removeObject(this);if(orgDispose)orgDispose.call(this);orgDispose=null;oResParser=null;};};_p.processAttributes=function(o,oNode){var attrs=oNode.attributes;var l=attrs.length;var name,value,parts,className,setterName,constr;for(var i=0;i<l;i++){name=attrs[i].nodeName;value=attrs[i].nodeValue;if(name=="xmlns"||name.indexOf(":")>0)
continue;if(name.indexOf(".")>0){parts=name.split(".");className=parts[0];setterName="set"+parts[1].capitalize();constr=window["Bi"+className]||window[className];if(typeof constr=="function"){if(typeof constr[setterName]=="function"){constr[setterName](o,value);}
else throw new Error("No such attached property \""+name+"\"");}
else throw new Error("No such class: \""+className+"\"");}
else o.setAttribute(name,value,this);if(name=="id")
this._addObject(o,value);}};_p.processChildNodes=function(obj,oNode){var tagName=oNode.localName||oNode.baseName;var re=new RegExp("^"+tagName+"\\.(.+)$");var cs=oNode.childNodes;var l=cs.length;var s;var emptyRe= /^\s*$/;for(var i=0;i<l;i++){if(re.test(cs[i].localName||cs[i].baseName)){var propertyName=RegExp.$1;var cs2=cs[i].childNodes;var l2=cs2.length;for(var j=0;j<l2;j++){if(cs2[j].nodeType==3){s=cs2[j].data;if(emptyRe.test(s))
continue;obj.setAttribute(propertyName,s,this);break;}
else if(cs2[j].nodeType==1){obj.setProperty(propertyName,this.fromNode(cs2[j]));break;}}}
else obj.addXmlNode(cs[i],this);}};_p.getComponentById=function(sId){var o=this._componentsById.item(sId);if(o){if(o.getDisposed())
this._removeObject(o);else return o;}
if(this.getLoaded()){var rn=this.getRootNode();var n=rn.selectSingleNode("//*[@id='"+sId+"']");if(!n)
return null;o=this.fromNode(n);if(o)
return o;}
return null;};_p.getLoaded=function(){return this._rootNode!=null||BiXmlLoader.prototype.getLoaded.call(this);};_p.dispose=function(){if(this.getDisposed())return;BiXmlLoader.prototype.dispose.call(this);var vs=this._componentsById.getValues();for(var i=0;i<vs.length;i++){this._removeObject(vs[i]);}
this._componentsById.dispose();delete this._componentsById;delete this._rootNode;};function BiSet()
{if(_biInPrototype)return;this._items=new BiHashTable;}
_p=_biExtend(BiSet,BiObject,"BiSet");_p.add=function(o)
{this._items.add(BiObject.toHashCode(o),o);};_p.remove=function(o)
{this._items.remove(BiObject.toHashCode(o));};_p.contains=function(o)
{return o&&this._items.hasKey(BiObject.toHashCode(o));};_p.clear=function()
{this._items.clear();};_p.toArray=function()
{return this._items.getValues();};_p.getValues=function()
{return this._items.getValues();};_p.dispose=function()
{if(this._disposed)return;BiObject.prototype.dispose.call(this);this._items.dispose();delete this._items;};function BiTimerManager(){if(_biInPrototype)return;if(BiTimerManager._singleton)
return BiTimerManager._singleton;BiObject.call(this);this._timers=new BiSet;BiTimerManager._singleton=this;application.addEventListener("dispose",this.dispose,this);}
_p=_biExtend(BiTimerManager,BiObject,"BiTimerManager");_p.add=function(oTimer){this._timers.add(oTimer);};_p.remove=function(oTimer){this._timers.remove(oTimer);};_p.dispose=function(){if(this._disposed)
return;BiObject.prototype.dispose.call(this);var vs=this._timers.getValues();for(var i=0;i<vs.length;i++){vs[i].dispose();}
this._timers.dispose();delete this._timers;application.removeEventListener("dispose",this.dispose,this);delete BiTimerManager._singleton;};function BiTimer(nInterval){if(_biInPrototype)return;BiEventTarget.call(this);(new BiTimerManager).add(this);if(nInterval>=0)
this._interval=nInterval;var oThis=this;this.__ontick=function()
{if(!oThis)return;if(oThis._disposed)
oThis=null;else oThis._ontick();};}
_p=_biExtend(BiTimer,BiEventTarget,"BiTimer");_p._enabled=false;_p._interval=1000;_p._intervalHandle=null;BiTimer.prototype.getEnabled=function(){return this._enabled;};BiTimer.prototype.setEnabled=function(v){this._enabled=v;};BiTimer.prototype.getInterval=function(){return this._interval;};_p.setInterval=function(nInterval){if(this._enabled)
this.stop();this._interval=nInterval;};_p.start=function(){if(this._enabled)
this.stop();this._enabled=true;this._intervalHandle=window.setInterval(this.__ontick,this._interval);};_p.stop=function(){this._enabled=false;window.clearInterval(this._intervalHandle);delete this._intervalHandle;};_p._ontick=function(){if(this._enabled)
{var e=new BiEvent("tick");this.dispatchEvent(e);e.dispose();application.flushLayoutQueue();}};_p.getIsStarted=function(){if(this._intervalHandle==null)
return false;else return true;}
_p.dispose=function(){if(this._disposed)return;BiEventTarget.prototype.dispose.call(this);this.stop();(new BiTimerManager).remove(this);this.__ontick();delete this.__ontick;delete this._interval;};BiTimer.callOnce=function(fun,time,obj){var t=new BiTimer(time!=null?time:1);t.addEventListener("tick",function(e){t.dispose();t=null;fun.call(obj,e);obj=fun=null;},obj);t.start();return t;};function BiApplication(){if(_biInPrototype)return;if(typeof application=="object")
return application;application=this;BiEventTarget.call(this);this._progressStatus="";this._adf=new BiAdf;}
var application;_p=_biExtend(BiApplication,BiEventTarget,"BiApplication");_p._version="3.0";BiApplication.prototype.getVersion=function(){return this._version;};_p.start=function(sRootPath,sAdfPath,oArgs)
{if(!BiBrowserCheck.supported){alert("Bindows is not supported for your Browser.");return;}
this.addEventListener("progressstatus",this._onprogressstatus);this._loadStatus=new LoadingStatus();this._loadStatus.setValue(2);if(arguments.length!=0)
this._buildArgumentsMapFromArguments(arguments);this._loadAdf();if(BiBrowserCheck.ie)
window.attachEvent("onunload",this._onunload);else window.addEventListener("unload",this._onunload,false);};_p._findRootPath=function(){var els=document.getElementsByTagName("script");var l=els.length;var p,src;var re= /(^|\/)js\/application\.js$/;for(var i=0;i<l;i++){src=els[i].src;if(re.test(src)){p=RegExp.leftContext;if(p.charAt(p.length-1)!="/"){p+="/";}
return new BiUri(this._uri,p);}}
return null;};_p._onunload=function()
{application.dispose();};_p._uri=new BiUri(window.location.href);_p._uriParams=new BiUri(window.location.href);_p._systemRootPath=new BiUri(window.location.href,"./");_p.getPath=function(){return this._systemRootPath;};_p.getAdfPath=function(){if(this._adfPath)
return this._adfPath;var p=this._uriParams.getParam("Adf");return this._adfPath=new BiUri(p,"./");};BiApplication.prototype.getProgressStatus=function(){return this._progressStatus;};BiApplication.prototype.setProgressStatus=function(v){this._progressStatus=v;};BiApplication.prototype.getWindow=function(){return this._window;};BiApplication.prototype.getAdf=function(){return this._adf;};BiApplication.prototype.getUri=function(){return this._uri;};_p._accessibilityMode=false;BiApplication.prototype.getAccessibilityMode=function(){return this._accessibilityMode;};BiApplication.prototype.setAccessibilityMode=function(v){this._accessibilityMode=v;};_p.setAccessibilityMode=function(b)
{var doneStartup=Boolean(this._window);if(b&&doneStartup&&!this._accessibilityMode)
{this._accessibilityMode=true;this._addAccessibilityPackage();BiTimer.callOnce(function(){this._initAccessibilityForAll(this._window);},0,this);}
else this._accessibilityMode=b||this._accessibilityMode&&doneStartup;}
_p._accessibilityDescription=null;_p._initAccessibilityForAll=function(c)
{c.initAccessibility();var children=c.getChildren();for(var i=0;i<children.length;i++)
this._initAccessibilityForAll(children[i]);}
BiApplication.prototype.getAccessibilityDescription=function(){return this._accessibilityDescription;};BiApplication.prototype.setAccessibilityDescription=function(v){this._accessibilityDescription=v;};_p._autoNameMapping=false;BiApplication.prototype.getAutoNameMapping=function(){return this._autoNameMapping;};_p.setAutoNameMapping=function(b)
{if(this._autoNameMapping!=b)
{this._autoNameMapping=b;this._adf.setAutoNameMapping(b);}};_p._focusOnLoad=true;_p.getFocusOnLoad=function()
{return this._focusOnLoad;};_p.setFocusOnLoad=function(b)
{this._focusOnLoad=b;};_p._buildArgumentsMapFromArguments=function(oArguments)
{var adfName="";var adfPath;var a0=oArguments[0];if(a0.charAt(a0.length-1)!="/")
a0+="/";this._systemRootPath=String(new BiUri(this._uri,a0));var re= /([\w]+)(?:\.[\w]+)?(?:$|\?)/;var ok=re.test(oArguments[1]);if(ok)
adfName=RegExp.$1;else this._reportError(this._getString("ApplicationIncorrectAdfArgument"));adfPath=String(new BiUri(this._uri,oArguments[1]));var uri=this._uriParams;uri.setParam("AdfName",adfName);uri.setParam("Adf",adfPath);uri.setParam("Params",oArguments.length-1);for(var i=2;i<oArguments.length;i++){uri.setParam("Param"+(i-2),oArguments[i]);}};_p._loadAdf=function(){this._progressStatus=this._getString("ApplicationLoadingAdf");this.dispatchEvent("progressstatus");this._resourceLoader=new BiResourceLoader;this._adf.addEventListener("load",this._onAdfLoaded,this);var adf=this._uriParams.getParam("Adf");if(this._uriParams.getParam("accessibilityMode")=="true")
this.setAccessibilityMode(true);if(adf!=null)
this._adf.load(adf);else this._reportError(this._getString("ApplicationNoAdf"));};_p._onAdfLoaded=function()
{this._progressStatus=this._getString("ApplicationAdfLoaded");this.dispatchEvent("progressstatus");if(this._adf.getError()){this._reportError(this._getString("ApplicationAdfLoadError"),this._getString("ApplicationAdfLoadErrorDetails",this._uriParams.getParam("Adf"),this._adf.getXmlHttp().status,this._adf.getXmlHttp().statusText));}
else{this._adf._interpret();if(BiBrowserCheck.ie)
BiTimer.callOnce(this._loadResources,1,this);else application._loadResources();}};_p.getResourceLoader=function()
{return this._resourceLoader;};_p.getResourceById=function(sId)
{if(this._resourceLoader==null)
return null;return this._resourceLoader.getResourceById(sId);};_p.getComponentById=function(sId)
{if(this._adf&&this._adf.getXmlResourceParser())
return this._adf.getXmlResourceParser().getComponentById(sId);return null;};_p._loadResources=function()
{var systemRootPath=this.getPath();var files;for(var i=0;i<this._defaultPackages.length;i++)
{files=this.getPackage(this._defaultPackages[i]);for(var j=0;j<files.length;j++)
{this._resourceLoader.addResource("script",new BiUri(systemRootPath,files[j]));}}
this._adf._addResources();this._addAccessibilityPackage();this._resourceLoader.addEventListener("progress",this._onprogressstatus,this);this._resourceLoader.addEventListener("load",this._onResourcesLoaded,this);this._progressStatus="Loading Resources";this._resourceLoader.load();};_p._addAccessibilityPackage=function(){if(this._accessibilityMode&&!this._508Added){var files=this.getPackage("Accessibility");var systemRootPath=this.getPath();for(var j=0;j<files.length;j++){this._resourceLoader.addResource("script",new BiUri(systemRootPath,files[j]));}
this._508Added=true;}};_p._onprogressstatus=function(e){if(!this._resourceLoader||this._resourceLoader.getCount()==0)
this._loadStatus.setValue(5);else{this._loadStatus.setValue(Math.max(5,Math.min(95,this._resourceLoader.getLoadedCount()/this._resourceLoader.getCount()*100)));this._progressStatus=this._getString("ApplicationLoadingResources",this._resourceLoader.getLoadedCount(),this._resourceLoader.getCount());}
this._loadStatus.setText(this.getProgressStatus());};_p._onResourcesLoaded=function(e)
{this._useTimersWorkAround=BiBrowserCheck.moz&&BiBrowserCheck.versionNumber<1.7;this._loadStatus.setText(this._getString("ApplicationLoadingCompleted"));this._loadStatus.setValue(100);this._window=new BiApplicationWindow;this._window._create();if(this._useTimersWorkAround)
{BiTimer.callOnce(this._onResourcesLoaded2,1,this);}
else this._onResourcesLoaded2();};_p._onResourcesLoaded2=function()
{this._adf.parseXmlResources();this.dispatchEvent("resourcesready");if(this._useTimersWorkAround)
{BiTimer.callOnce(this._onResourcesLoaded3,1,this);}
else this._onResourcesLoaded3();};_p._onResourcesLoaded3=function()
{this.flushLayoutQueue();if(application._loadStatus){application._loadStatus.dispose();delete application._loadStatus;}
if(this._focusOnLoad)
{try
{window.focus();}
catch(ex){}}
if(this._useTimersWorkAround)
{BiTimer.callOnce(this._onResourcesLoaded4,1,this);}
else {application._onResourcesLoaded4();}};_p._onResourcesLoaded4=function()
{var appClassName=this._uriParams.getParam("AdfName");var uri=this._uriParams;var argc=Number(uri.getParam("Params"));var argv=new Array(argc);for(var i=0;i<argc;i++)
argv[i]=uri.getParam("Param"+i);if(window[appClassName]&&typeof window[appClassName].main=="function")
{window[appClassName].main.apply(window[appClassName],argv);this.flushLayoutQueue();}
this.dispatchEvent("load");this.flushLayoutQueue();};_p._reportError=function(s,s2){if(this._loadStatus)
this._loadStatus.setText(s);throw new Error(s2||s);};_p.dispose=function()
{if(this._disposed)return;this.dispatchEvent("dispose");if(BiBrowserCheck.ie)
window.detachEvent("onunload",this._onunload);else window.removeEventListener("unload",this._onunload,false);this.disposeFields("_adfPath","_systemRootPath","_themeManager","_window","_loadStatus","_resourceLoader","_adf","_inactivityTimeout","_uri","_uriParams");BiEventTarget.prototype.dispose.call(this);application=null;};_p.setAttribute=function(sName,sValue,oParser)
{switch(sName)
{case"defaultPackages":this.setProperty(sName,sValue.split(/\s*,\s*/));break;default:BiEventTarget.prototype.setAttribute.apply(this,arguments);}};_p.flushLayoutQueue=function()
{if(typeof BiComponent==BiObject.TYPE_FUNCTION)
{BiComponent.flushLayoutQueue();}};_p.getThemeManager=function()
{if(!this._themeManager){this._themeManager=new BiThemeManager;}
return this._themeManager;};_p.getTheme=function()
{return this.getThemeManager().getDefaultTheme();};BiApplication.prototype.getInactivityTimeout=function(){return this._inactivityTimeout;};BiApplication.prototype.setInactivityTimeout=function(n){n=Number(n)||0;if(this._inactivityTimeout!=n){this._inactivityTimeout=n;if(!this._inactivityTimer){this._inactivityTimer=new BiTimer;this._inactivityTimer.addEventListener("tick",function(e){this._inactivityTimer.stop();this.dispatchEvent("inactive");},this);}
this._inactivityTimer.setInterval(60000*n);if(n>0)
this._inactivityTimer.start();else this._inactivityTimer.stop();}};BiApplication.prototype.restartInactivityTimer=function(){if(this._inactivityTimer&&this._inactivityTimeout>0){this._inactivityTimer.start();}};_p._defaultPackages=["Core","Gui","Layout","DateTime","XmlRpc","Grid","TreeView"];_p.getPackage=function(sName)
{if(sName in this._packages)
return this._packages[sName];return[];};_p.getPackages=function(){var res=[];for(var n in this._packages){res.push(n);}
return res;};_p.addPackage=function(sName,oFiles)
{this._packages[sName]=oFiles;};BiApplication.prototype.getDefaultPackages=function(){return this._defaultPackages;};BiApplication.prototype.setDefaultPackages=function(v){this._defaultPackages=v;};BiApplication.prototype.getStringBundle=function(){return this._stringBundle;};BiApplication.prototype.setStringBundle=function(v){this._stringBundle=v;};_p._getString=function(s)
{var o=this._stringBundle;return o.getFormattedString.apply(o,arguments);};_p._stringBundle=new BiStringBundle;_p._stringBundle.addBundle("en",{ApplicationIncorrectAdfArgument:"The ADF argument is incorrect",ApplicationLoadingAdf:"Loading Application Description File",ApplicationNoAdf:"No ADF specified",ApplicationAdfLoaded:"Application Description File Loaded",ApplicationAdfLoadError:"Error loading ADF",ApplicationAdfLoadErrorDetails:"Error loading ADF\nURI: %1\nStatus: %2, %3",ApplicationLoadingResources:"Loading resources (%1/%2)",ApplicationLoadingCompleted:"Loading completed"});application=new BiApplication;application._packages_ie={Launcher:["js/BiXml.js","js/BiBrowserCheck.js","js/BiCore.js"],Core:["js/BiStringBuffer.js","js/BiMouseEvent.js","js/BiKeyboardEvent.js","js/BiFocusEvent.js","js/BiEventManager.js","js/BiBorder.js","js/BiComponent.js","js/BiSort.js","js/BiVmlComponent.js","js/BiInlineComponent.js","js/BiApplicationWindow.js","js/BiFocusElementWrapper.js","js/BiFocusManager.js","js/BiFont.js","js/BiImagePreloader.js","js/BiImage.js","js/BiLabel.js","js/BiDragEvent.js","js/BiDragAndDropManager.js","js/BiPopupManager.js","js/BiPopup.js","js/BiToolTipManager.js","js/BiToolTip.js","js/BiJson.js","js/BiWebService.js","js/BiTextParser.js","js/BiL10nString.js"],Loaders:["js/loaders/BiSerializedLoader.js","js/loaders/BiObjectLoader.js","js/loaders/BiJsonLoader.js","js/loaders/BiResources.js","js/loaders/BiScript.js","js/loaders/BiScriptLoader.js","js/loaders/BiCssLoader.js","js/loaders/BiImageLoader.js","js/loaders/BiStyle.js"],OlapGrid:["js/olapgrid/BiOlapGridCellInfo.js","js/olapgrid/BiOlapGridDropMarker.js","js/olapgrid/BiOlapGridDragEvent.js","js/BiAttachedComponentModel.js","js/BiInlineEditModel.js","js/olapgrid/BiOlapGrid.js","js/olapgrid/BiOlapGridDataModel.js","js/olapgrid/BiOlapGridResizeOutline.js","js/olapgrid/BiArea.js","js/olapgrid/BiOlapGridSelectionModel.js","js/olapgrid/BiOlapGridStateManager.js","js/olapgrid/BiOrderedIterator.js","js/olapgrid/BiOlapGridViewManager.js"],Charting:["js/chartingbase/BiGraphBase.js","js/chartingbase/BiChartCategory.js","js/chartingbase/BiChartSeries.js","js/chartingbase/BiGridChart.js","js/chartingbase/BiChartLegend.js","js/chartingbase/BiChartPoint.js","js/chartingbase/BiChartPresentation.js","js/chartingbase/BiChartMouseEvent.js","js/chartingbase/BiAbstractChartBase.js","js/chartingbase/BiAbstractChartSectionBase.js","js/chartingbase/BiChartValueAxisBase.js","js/chartingbase/BiChartCategoryAxisBase.js","js/chartingbase/BiChartAreaBase.js","js/chartingbase/BiChartGridLinesBase.js","js/charting/BiGraph.js","js/charting/BiGraphContentArea.js","js/charting/BiChartArea.js","js/charting/BiVmlLabel.js","js/charting/BiChartValueAxis.js","js/charting/BiChartCategoryAxis.js","js/charting/BiChartGridLines.js","js/charting/BiAbstractChart.js","js/charting/BiAbstractChartSection.js","js/charting/BiLineChart.js","js/charting/BiColumnChart.js","js/charting/BiPieChart.js","js/charting2/BiChartArea2.js","js/charting2/BiAbstractChart2.js","js/charting2/BiAbstractChartSection2.js","js/charting2/BiChartCategoryAxis2.js","js/charting2/BiChartValueAxis2.js","js/charting2/BiColumnChart2.js","js/charting2/BiChartGridLines2.js","js/charting2/BiGraph2.js"],WebService2:["js/webservice2/BiXmlDefinitionsDocument.js","js/webservice2/BiWsdlDefinitionDictionary.js","js/webservice2/BiSchemaError.js","js/webservice2/BiSchemaType.js","js/webservice2/BiSchemaSimpleContentType.js","js/webservice2/BiSchemaPrimitiveTypes.js","js/webservice2/BiSchemaWildcard.js","js/webservice2/BiSchemaParticle.js","js/webservice2/BiSchemaModelGroup.js","js/webservice2/BiSchemaAllModelGroup.js","js/webservice2/BiSchemaChoiceModelGroup.js","js/webservice2/BiSchemaSequenceModelGroup.js","js/webservice2/BiSchemaGroupDef.js","js/webservice2/BiSchemaAttributeBag.js","js/webservice2/BiSchemaAttrUse.js","js/webservice2/BiSchemaSimpleType.js","js/webservice2/BiSchemaComplexType.js","js/webservice2/BiSchemaAttr.js","js/webservice2/BiSchemaElement.js","js/webservice2/BiSchemaGroupDef.js","js/webservice2/BiSchemaAttrGroupDef.js","js/webservice2/BiSchema.js","js/webservice2/BiWebServiceError.js","js/webservice2/BiWsdlProtocolInfoFactory.js","js/webservice2/BiWsdlUtils.js","js/webservice2/BiWsdlBinding.js","js/webservice2/BiWsdlServicePort.js","js/webservice2/BiWsdlService.js","js/webservice2/BiWsdlPortType.js","js/webservice2/BiWsdlBindingOperation.js","js/webservice2/BiWsdlPortOperation.js","js/webservice2/BiWsdlMessage.js","js/webservice2/BiWsdlMessagePart.js","js/webservice2/BiWsdl.js","js/webservice2/BiWsdlSoapPortProtocolInfo.js","js/webservice2/BiWsdlBindingOpSoapInfo.js","js/webservice2/BiWsdlBindingSoapInfo.js","js/webservice2/BiWsOpHandler.js","js/webservice2/BiWsCallCompleteEvent.js","js/webservice2/BiWebService2.js","js/webservice2/BiWebServiceFaultError.js","js/webservice2/BiWsSoapSerializerNsManager.js","js/webservice2/BiWsSoapSerializer.js","js/webservice2/BiWsSoapSchemaBasedSerializer.js","js/webservice2/BiWsSoapLiteralSerializer.js","js/webservice2/BiWsSoapEncodedSerializer.js","js/webservice2/BiWsSoapDeserializer.js","js/webservice2/BiWsSoapSchemaBasedDeserializer.js","js/webservice2/BiWsSoapLiteralDeserializer.js","js/webservice2/BiWsSoapEncodedDeserializer.js","js/webservice2/BiWsSoapBodyProcessor.js","js/webservice2/BiWsSoapOpHandler.js","js/webservice2/BiWsSoapDocumentBodyProcessor.js","js/webservice2/BiWsSoapRpcBodyProcessor.js"],TreeView:["js/treeview/BiTreeViewStateManager.js","js/treeview/BiTreeViewSelectionModel.js","js/treeview/BiTreeViewViewManager.js","js/treeview/BiTreeViewResizeOutline.js","js/treeview/BiTreeViewDragHeader.js","js/treeview/BiTreeViewDragHeaderMarker.js","js/treeview/BiTreeViewCellInfo.js","js/BiAttachedComponentModel.js","js/BiInlineEditModel.js","js/treeview/BiTreeView.js","js/treeview/BiTreeViewDataModel.js","js/treeview/BiTreeViewDataModelEvent.js","js/treeview/BiGridDataModel.js","js/treeview/BiTreeNode2.js","js/treeview/BiTreeDataModel.js","js/treeview/BiGrid2.js","js/treeview/BiGrid2DataModel.js","js/treeview/BiGrid2ViewManager.js","js/treeview/BiTree2.js","js/treeview/BiTree2DataModel.js","js/treeview/BiTree2ViewManager.js","js/treeview/BiDataSetDataModel.js","js/treeview/BiSingleTree.js","js/treeview/BiSingleTreeDataModel.js","js/treeview/BiSingleTreeViewManager.js"],DateTime:["js/datetime/BiDateFormatSymbols.js","js/datetime/BiDateFormat.js","js/datetime/BiCalendar.js","js/datetime/BiDatePicker.js","js/datetime/BiTimePicker.js","js/datetime/de.js","js/datetime/en.js","js/datetime/es.js","js/datetime/fr.js","js/datetime/he.js","js/datetime/ja.js","js/datetime/ko.js","js/datetime/pt.js","js/datetime/ru.js","js/datetime/sv.js","js/datetime/tr.js","js/datetime/zh-CN.js","js/datetime/zh-TW.js"],Layout:["js/layout/BiGridPanel.js","js/layout/BiDockPanel.js","js/layout/BiBox.js","js/layout/BiFlowPanel.js","js/layout/BiGridPanel2.js"],XmlRpc:["js/BiBase64.js","js/BiXmlRpcInt.js","js/BiXmlRpcDouble.js","js/BiXmlRpc.js","js/BiXmlRpcError.js","js/BiXmlRpcCallCompleteEvent.js"],Grid:["js/BiGridHeaders.js","js/BiAbstractGrid.js","js/BiTree.js","js/BiGrid.js"],Gui:["js/BiCommand.js","js/BiButton.js","js/BiRepeatButton.js","js/BiCheckBox.js","js/BiRadioButton.js","js/BiRadioGroup.js","js/BiTextField.js","js/BiIpField.js","js/BiPasswordField.js","js/BiTextArea.js","js/BiGroupBox.js","js/BiGroupBoxTitle.js","js/BiRangeModel.js","js/BiProgressBar.js","js/BiSlider.js","js/BiSpinner.js","js/BiScrollBar.js","js/BiSelectionModel.js","js/BiList.js","js/BiListItem.js","js/BiListSelectionModel.js","js/BiComboBox.js","js/BiSplitPane.js","js/BiStatusBar.js","js/BiToolBar.js","js/BiToolBarSplitMenuButton.js","js/BiToolBarSplitToggleMenuButton.js","js/BiTab.js","js/BiIframe.js","js/BiRichEdit.js","js/BiPrintFrame.js","js/BiGauge.js","js/poslib.js","js/scrollbutton.js","js/menu4.js","js/BiMenu.js","js/BiMoveEvent.js","js/BiResizeEvent.js","js/BiMoveHandle.js","js/BiResizeHandle.js","js/BiWindow.js","js/BiWindowManager.js","js/BiDesktopPane.js","js/BiOptionPane.js","js/BiDialog.js","js/BiColorPicker.js","js/BiWizardPane.js","js/BiWizard.js","js/BiDataSet.js","js/BiXsTypeConverter.js","js/BiXmlDataSet.js","js/BiDataBinding.js","js/BiAccordionPane.js","js/BiTreeViewBase.js","js/BiImageMap.js"],Gauge2:["js/gauge2/BiGauge2.ie.js","js/gauge2/BiGauge2Group.ie.js","js/gauge2/BiGauge2Component.ie.js","js/gauge2/BiAbstractGauge2Border.ie.js","js/gauge2/BiGauge2CircularBorder.ie.js","js/gauge2/BiGauge2RectangularBorder.ie.js","js/gauge2/BiGauge2ImageBorder.ie.js","js/gauge2/BiAbstractGauge2Filler.js","js/gauge2/BiGauge2PlainColorFiller.ie.js","js/gauge2/BiAbstractGauge2GradientFiller.ie.js","js/gauge2/BiGauge2RadialGradientFiller.ie.js","js/gauge2/BiGauge2RingGradientFiller.ie.js","js/gauge2/BiGauge2LinearGradientFiller.ie.js","js/gauge2/BiAbstractGauge2Range.ie.js","js/gauge2/BiGauge2RadialRange.ie.js","js/gauge2/BiGauge2LinearRange.ie.js","js/gauge2/BiAbstractGauge2Ticks.ie.js","js/gauge2/BiGauge2RadialTicks.ie.js","js/gauge2/BiGauge2LinearTicks.ie.js","js/gauge2/BiAbstractGauge2Scale.ie.js","js/gauge2/BiGauge2RadialScale.ie.js","js/gauge2/BiGauge2LinearScale.ie.js","js/gauge2/BiAbstractGauge2Cap.js","js/gauge2/BiGauge2BasicCap.ie.js","js/gauge2/BiAbstractGauge2ValueMarker.ie.js","js/gauge2/BiGauge2RadialNeedle.ie.js","js/gauge2/BiGauge2LinearNeedle.ie.js","js/gauge2/BiAbstractGauge2ScaleSection.ie.js","js/gauge2/BiGauge2RadialScaleSection.ie.js","js/gauge2/BiGauge2Label.ie.js"],Animation:["js/animation/BiMath.js","js/animation/BiLine.js","js/animation/BiFrameProgressionEvent.js","js/animation/BiFpsGenerator.js","js/animation/BiComponentAnimation.js","js/animation/BiOpacityAnimator.js","js/animation/BiLocationAnimator.js","js/animation/BiSizeAnimator.js","js/animation/BiAnimationRange.js"],Accessibility:["js/accessibility/BiAccessibilityManager.js","js/accessibility/BiComponent.508.js","js/accessibility/BiApplicationWindow.508.js","js/accessibility/BiSlider.508.js","js/accessibility/BiComboBox.508.js","js/accessibility/BiList.508.js","js/accessibility/BiSpinner.508.js","js/accessibility/BiCalendar.508.js","js/accessibility/BiDatePicker.508.js","js/accessibility/BiDesktopPane.508.js","js/accessibility/BiGroupBox.508.js","js/accessibility/BiRichEdit.508.js","js/accessibility/BiIframe.508.js","js/accessibility/BiImage.508.js","js/accessibility/BiIpField.508.js","js/accessibility/BiLabel.508.js","js/accessibility/BiGauge.508.js","js/accessibility/BiWindow.508.js","js/accessibility/BiTimePicker.508.js","js/accessibility/BiToolBar.508.js","js/accessibility/BiTab.508.js","js/accessibility/BiMenu.508.js","js/accessibility/BiRadioButton.508.js","js/accessibility/BiCheckBox.508.js","js/accessibility/BiGrid.508.js","js/accessibility/BiTree.508.js","js/accessibility/BiTreeViewBase.508.js","js/accessibility/BiTreeView.508.js","js/accessibility/BiGrid2.508.js","js/accessibility/BiDialog.508.js","js/accessibility/BiColorPicker.508.js","js/accessibility/BiWizard.508.js","js/accessibility/BiAccordionPane.508.js","js/accessibility/BiProgressBar.508.js","js/accessibility/BiUndeterminedProgressBar.508.js","js/accessibility/BiStatusBarPanel.508.js","js/accessibility/BiOlapGrid.508.js","js/accessibility/BiGraph.508.js","js/accessibility/BiGraph2.508.js","js/accessibility/BiAbstractGrid.508.js","js/accessibility/BiButton.508.js","js/accessibility/BiTextField.508.js","js/accessibility/BiPasswordField.508.js"]};application._packages_moz={Launcher:["js/BiXml.js","js/BiBrowserCheck.js","js/BiCore.js"],Core:["js/BiStringBuffer.js","js/BiMouseEvent.js","js/BiKeyboardEvent.js","js/BiFocusEvent.js","js/BiEventManager.js","js/BiBorder.js","js/BiComponent.js","js/BiSort.js","js/BiSvgComponent.js","js/BiInlineComponent.js","js/BiApplicationWindow.js","js/BiFocusElementWrapper.js","js/BiFocusManager.js","js/BiFont.js","js/BiImagePreloader.js","js/BiImage.js","js/BiLabel.js","js/BiDragEvent.js","js/BiDragAndDropManager.js","js/BiPopupManager.js","js/BiPopup.js","js/BiToolTipManager.js","js/BiToolTip.js","js/BiJson.js","js/BiTextParser.js","js/BiL10nString.js"],Loaders:["js/loaders/BiSerializedLoader.js","js/loaders/BiObjectLoader.js","js/loaders/BiJsonLoader.js","js/loaders/BiResources.js","js/loaders/BiScript.js","js/loaders/BiScriptLoader.js","js/loaders/BiCssLoader.js","js/loaders/BiImageLoader.js","js/loaders/BiStyle.js"],OlapGrid:["js/olapgrid/BiOlapGridCellInfo.js","js/olapgrid/BiOlapGridDropMarker.js","js/olapgrid/BiOlapGridDragEvent.js","js/BiAttachedComponentModel.js","js/BiInlineEditModel.js","js/olapgrid/BiOlapGrid.js","js/olapgrid/BiOlapGridDataModel.js","js/olapgrid/BiOlapGridResizeOutline.js","js/olapgrid/BiArea.js","js/olapgrid/BiOlapGridSelectionModel.js","js/olapgrid/BiOlapGridStateManager.js","js/olapgrid/BiOrderedIterator.js","js/olapgrid/BiOlapGridViewManager.js"],Charting:["js/chartingbase/BiGraphBase.js","js/chartingbase/BiChartCategory.js","js/chartingbase/BiChartSeries.js","js/chartingbase/BiGridChart.js","js/chartingbase/BiChartLegend.js","js/chartingbase/BiChartPoint.js","js/chartingbase/BiChartPresentation.js","js/chartingbase/BiChartMouseEvent.js","js/chartingbase/BiAbstractChartBase.js","js/chartingbase/BiAbstractChartSectionBase.js","js/chartingbase/BiChartValueAxisBase.js","js/chartingbase/BiChartCategoryAxisBase.js","js/chartingbase/BiChartAreaBase.js","js/chartingbase/BiChartGridLinesBase.js","js/chartingbase_svg/BiChartComponent.js","js/chartingbase_svg/BiSvgGraphBase.js","js/chartingbase_svg/BiSvgAbstractChartBase.js","js/chartingbase_svg/BiSvgAbstractChartSectionBase.js","js/chartingbase_svg/BiSvgChartValueAxisBase.js","js/chartingbase_svg/BiSvgChartCategoryAxisBase.js","js/chartingbase_svg/BiSvgChartAreaBase.js","js/chartingbase_svg/BiSvgChartGridLinesBase.js","js/charting2/BiChartArea2.js","js/charting2/BiAbstractChart2.js","js/charting2/BiAbstractChartSection2.js","js/charting2/BiChartCategoryAxis2.js","js/charting2/BiChartValueAxis2.js","js/charting2/BiColumnChart2.js","js/charting2/BiChartGridLines2.js","js/charting2/BiGraph2.js","js/charting_svg/BiSvgGraph.js","js/charting_svg/BiSvgGraphContentArea.js","js/charting_svg/BiSvgChartArea.js","js/charting_svg/BiSvgChartValueAxis.js","js/charting_svg/BiSvgChartCategoryAxis.js","js/charting_svg/BiSvgChartGridLines.js","js/charting_svg/BiSvgAbstractChart.js","js/charting_svg/BiSvgAbstractChartSection.js","js/charting_svg/BiSvgLineChart.js","js/charting_svg/BiSvgColumnChart.js","js/charting_svg/BiSvgPieChart.js","js/charting_svg/BiSvgFill.js"],WebService2:["js/webservice2/BiXmlDefinitionsDocument.js","js/webservice2/BiWsdlDefinitionDictionary.js","js/webservice2/BiSchemaError.js","js/webservice2/BiSchemaType.js","js/webservice2/BiSchemaSimpleContentType.js","js/webservice2/BiSchemaPrimitiveTypes.js","js/webservice2/BiSchemaWildcard.js","js/webservice2/BiSchemaParticle.js","js/webservice2/BiSchemaModelGroup.js","js/webservice2/BiSchemaAllModelGroup.js","js/webservice2/BiSchemaChoiceModelGroup.js","js/webservice2/BiSchemaSequenceModelGroup.js","js/webservice2/BiSchemaGroupDef.js","js/webservice2/BiSchemaAttributeBag.js","js/webservice2/BiSchemaAttrUse.js","js/webservice2/BiSchemaSimpleType.js","js/webservice2/BiSchemaComplexType.js","js/webservice2/BiSchemaAttr.js","js/webservice2/BiSchemaElement.js","js/webservice2/BiSchemaGroupDef.js","js/webservice2/BiSchemaAttrGroupDef.js","js/webservice2/BiSchema.js","js/webservice2/BiWebServiceError.js","js/webservice2/BiWsdlProtocolInfoFactory.js","js/webservice2/BiWsdlUtils.js","js/webservice2/BiWsdlBinding.js","js/webservice2/BiWsdlServicePort.js","js/webservice2/BiWsdlService.js","js/webservice2/BiWsdlPortType.js","js/webservice2/BiWsdlBindingOperation.js","js/webservice2/BiWsdlPortOperation.js","js/webservice2/BiWsdlMessage.js","js/webservice2/BiWsdlMessagePart.js","js/webservice2/BiWsdl.js","js/webservice2/BiWsdlSoapPortProtocolInfo.js","js/webservice2/BiWsdlBindingOpSoapInfo.js","js/webservice2/BiWsdlBindingSoapInfo.js","js/webservice2/BiWsOpHandler.js","js/webservice2/BiWsCallCompleteEvent.js","js/webservice2/BiWebService2.js","js/webservice2/BiWebServiceFaultError.js","js/webservice2/BiWsSoapSerializerNsManager.js","js/webservice2/BiWsSoapSerializer.js","js/webservice2/BiWsSoapSchemaBasedSerializer.js","js/webservice2/BiWsSoapLiteralSerializer.js","js/webservice2/BiWsSoapEncodedSerializer.js","js/webservice2/BiWsSoapDeserializer.js","js/webservice2/BiWsSoapSchemaBasedDeserializer.js","js/webservice2/BiWsSoapLiteralDeserializer.js","js/webservice2/BiWsSoapEncodedDeserializer.js","js/webservice2/BiWsSoapBodyProcessor.js","js/webservice2/BiWsSoapOpHandler.js","js/webservice2/BiWsSoapDocumentBodyProcessor.js","js/webservice2/BiWsSoapRpcBodyProcessor.js"],TreeView:["js/treeview/BiTreeViewStateManager.js","js/treeview/BiTreeViewSelectionModel.js","js/treeview/BiTreeViewViewManager.js","js/treeview/BiTreeViewResizeOutline.js","js/treeview/BiTreeViewDragHeader.js","js/treeview/BiTreeViewDragHeaderMarker.js","js/treeview/BiTreeViewCellInfo.js","js/BiAttachedComponentModel.js","js/BiInlineEditModel.js","js/treeview/BiTreeView.js","js/treeview/BiTreeViewDataModel.js","js/treeview/BiTreeViewDataModelEvent.js","js/treeview/BiGridDataModel.js","js/treeview/BiTreeNode2.js","js/treeview/BiTreeDataModel.js","js/treeview/BiGrid2.js","js/treeview/BiGrid2DataModel.js","js/treeview/BiGrid2ViewManager.js","js/treeview/BiTree2.js","js/treeview/BiTree2DataModel.js","js/treeview/BiTree2ViewManager.js","js/treeview/BiDataSetDataModel.js","js/treeview/BiSingleTree.js","js/treeview/BiSingleTreeDataModel.js","js/treeview/BiSingleTreeViewManager.js"],DateTime:["js/datetime/BiDateFormatSymbols.js","js/datetime/BiDateFormat.js","js/datetime/BiCalendar.js","js/datetime/BiDatePicker.js","js/datetime/BiTimePicker.js","js/datetime/de.js","js/datetime/en.js","js/datetime/es.js","js/datetime/fr.js","js/datetime/he.js","js/datetime/ja.js","js/datetime/ko.js","js/datetime/pt.js","js/datetime/ru.js","js/datetime/sv.js","js/datetime/tr.js","js/datetime/zh-CN.js","js/datetime/zh-TW.js"],Layout:["js/layout/BiGridPanel.js","js/layout/BiDockPanel.js","js/layout/BiBox.js","js/layout/BiFlowPanel.js","js/layout/BiGridPanel2.js"],XmlRpc:["js/BiBase64.js","js/BiXmlRpcInt.js","js/BiXmlRpcDouble.js","js/BiXmlRpc.js","js/BiXmlRpcError.js","js/BiXmlRpcCallCompleteEvent.js"],Grid:["js/BiGridHeaders.js","js/BiAbstractGrid.js","js/BiTree.js","js/BiGrid.js"],Gui:["js/BiCommand.js","js/BiButton.js","js/BiRepeatButton.js","js/BiCheckBox.js","js/BiRadioButton.js","js/BiRadioGroup.js","js/BiTextField.js","js/BiIpField.js","js/BiPasswordField.js","js/BiTextArea.js","js/BiGroupBox.js","js/BiGroupBoxTitle.js","js/BiRangeModel.js","js/BiProgressBar.js","js/BiSlider.js","js/BiSpinner.js","js/BiScrollBar.js","js/BiSelectionModel.js","js/BiList.js","js/BiListItem.js","js/BiListSelectionModel.js","js/BiComboBox.js","js/BiSplitPane.js","js/BiSvgGauge.js","js/BiStatusBar.js","js/BiToolBar.js","js/BiToolBarSplitMenuButton.js","js/BiToolBarSplitToggleMenuButton.js","js/BiTab.js","js/BiIframe.js","js/BiRichEdit.js","js/BiPrintFrame.js","js/BiMenu.moz.js","js/BiMoveEvent.js","js/BiResizeEvent.js","js/BiMoveHandle.js","js/BiResizeHandle.js","js/BiWindow.js","js/BiWindowManager.js","js/BiDesktopPane.js","js/BiOptionPane.js","js/BiDialog.js","js/BiColorPicker.js","js/BiWizardPane.js","js/BiWizard.js","js/BiDataSet.js","js/BiXsTypeConverter.js","js/BiXmlDataSet.js","js/BiDataBinding.js","js/BiAccordionPane.js","js/BiTreeViewBase.js","js/BiImageMap.js"],Gauge2:["js/gauge2/BiGauge2.moz.js","js/gauge2/BiGauge2Group.moz.js","js/gauge2/BiGauge2Component.moz.js","js/gauge2/BiAbstractGauge2Border.moz.js","js/gauge2/BiGauge2CircularBorder.moz.js","js/gauge2/BiGauge2RectangularBorder.moz.js","js/gauge2/BiGauge2ImageBorder.moz.js","js/gauge2/BiAbstractGauge2Filler.js","js/gauge2/BiGauge2PlainColorFiller.moz.js","js/gauge2/BiAbstractGauge2GradientFiller.moz.js","js/gauge2/BiGauge2RadialGradientFiller.moz.js","js/gauge2/BiGauge2RingGradientFiller.moz.js","js/gauge2/BiGauge2LinearGradientFiller.moz.js","js/gauge2/BiAbstractGauge2Range.moz.js","js/gauge2/BiGauge2RadialRange.moz.js","js/gauge2/BiGauge2LinearRange.moz.js","js/gauge2/BiAbstractGauge2Ticks.moz.js","js/gauge2/BiGauge2RadialTicks.moz.js","js/gauge2/BiGauge2LinearTicks.moz.js","js/gauge2/BiAbstractGauge2Scale.moz.js","js/gauge2/BiGauge2RadialScale.moz.js","js/gauge2/BiGauge2LinearScale.moz.js","js/gauge2/BiAbstractGauge2Cap.js","js/gauge2/BiGauge2BasicCap.moz.js","js/gauge2/BiAbstractGauge2ValueMarker.moz.js","js/gauge2/BiGauge2RadialNeedle.moz.js","js/gauge2/BiGauge2LinearNeedle.moz.js","js/gauge2/BiAbstractGauge2ScaleSection.moz.js","js/gauge2/BiGauge2RadialScaleSection.moz.js","js/gauge2/BiGauge2Label.moz.js"],Animation:["js/animation/BiMath.js","js/animation/BiLine.js","js/animation/BiFrameProgressionEvent.js","js/animation/BiFpsGenerator.js","js/animation/BiComponentAnimation.js","js/animation/BiOpacityAnimator.js","js/animation/BiLocationAnimator.js","js/animation/BiSizeAnimator.js","js/animation/BiAnimationRange.js"],Accessibility:[]};application._packages=BiBrowserCheck.ie?application._packages_ie:application._packages_moz;function BiComponent(sName){if(_biInPrototype)return;BiEventTarget.call(this);if(sName)this._name=sName;this._children=[];this._style={};this._htmlProperties={id:this._className+(BiComponent.STRING_DASH+(++BiComponent._componentCount)),className:this._cssClassName,unselectable:"on"};this._htmlAttributes={};if(application._accessibilityMode){this.initAccessibility();}}
_p=_biExtend(BiComponent,BiEventTarget,"BiComponent");BiComponent._componentCount=0;_p._enabled=true;_p._tabIndex= -1;_p._tagName="DIV";_p._cssClassName="bi-component";_p._opacity=1;_p._visible=true;BiComponent.STYLE_AUTO="auto";BiComponent.STYLE_HIDDEN="hidden";BiComponent.STYLE_SCROLL="scroll";BiComponent.STYLE_NONE="none";BiComponent.STYLE_INHERIT="inherit";BiComponent.STYLE_VISIBLE="visible";BiComponent.STYLE_RTL="rtl";BiComponent.STYLE_LTR="ltr";BiComponent.STYLE_MOZ_SCROLLBARS_HORIZONTAL="-moz-scrollbars-horizontal";BiComponent.STYLE_MOZ_SCROLLBARS_VERTICAL="-moz-scrollbars-vertical";BiComponent.STYLE_MOZ_SCROLLBARS_NONE="-moz-scrollbars-none";BiComponent.STRING_DASH="-";BiComponent.STRING_MOVE="move";BiComponent.STRING_RESIZE="resize";BiComponent.STRING_SIZE="size";if(BiBrowserCheck.ie){BiComponent.STRING_LEFT="pixelLeft";BiComponent.STRING_TOP="pixelTop";BiComponent.STRING_WIDTH="pixelWidth";BiComponent.STRING_HEIGHT="pixelHeight";}
else{BiComponent.STRING_LEFT="left";BiComponent.STRING_TOP="top";BiComponent.STRING_WIDTH="width";BiComponent.STRING_HEIGHT="height";BiComponent.STRING_PX="px";}
_p.add=function(oChild,oBefore,bAnonymous){var p=oChild._parent;if(oBefore==null){if(p!=null)
p.remove(oChild);this._children.push(oChild);}
else{if(oBefore._parent!=this)
throw new Error("Can only add components before siblings");if(p!=null)
p.remove(oChild);this._children.insertBefore(oChild,oBefore);}
oChild._anonymous=Boolean(bAnonymous);oChild._parent=this;if(this._created)
oChild._addHtmlElementToParent(this,oBefore);};_p.remove=function(oChild){if(oChild._parent!=this)
throw new Error("Can only remove children");oChild._parent=null;oChild._anonymous=false;if(this._children)
this._children.remove(oChild);this._removeHtmlElement(oChild);return oChild;};_p.removeAll=function(){var cs=this._children;this._children=[];var l=cs.length;for(var i=0;i<l;i++){var c=cs[i];if(c._anonymous){this._children.push(c);}
else{c.dispose();}}};_p.getParent=function(){if(this._parent==null||!this._parent._anonymous)
return this._parent;return this._parent.getParent();};_p.setParent=function(p){if(p!=null&&p!=this._parent){p.add(this);}else if(this._parent!=null){this._parent.remove(this);}};_p.getChildren=function(){var res=[];var cs=this._children;var l=cs.length;for(var i=0;i<l;i++){if(!cs[i]._anonymous)
res.push(cs[i]);}
return res;};_p.setChildren=function(cs){this.removeAll();if(cs!=null){for(var i=0;i<cs.length;i++){this.add(cs[i]);}}};_p.setStyleProperty=function(sProp,sValue){if(sValue==String.EMPTY){delete this._style[sProp];}else{this._style[sProp]=sValue;}
if(this._created)
this._element.style[sProp]=sValue;};_p.getStyleProperty=function(sProp){if(this._created){if(BiBrowserCheck.ie)
return this._element.currentStyle[sProp];else return this._document.defaultView.getComputedStyle(this._element,String.EMPTY)[sProp];}
else return this._style[sProp];};_p.removeStyleProperty=function(sProp){delete this._style[sProp];if(this._created)
this._element.style[sProp]=String.EMPTY;};_p.getHtmlProperty=function(sProp){if(this._created)
return this._element[sProp];return this._htmlProperties[sProp];};_p.setHtmlProperty=function(sProp,oValue){this._htmlProperties[sProp]=oValue;if(this._created)
this._element[sProp]=oValue;};_p.removeHtmlProperty=function(sProp){delete this._htmlProperties[sProp];if(this._created){if(BiBrowserCheck.ie)
this._element.removeAttribute(sProp);else delete this._element[sProp];}};_p._getHtmlAttribute=function(sName){if(this._created)
return this._element.getAttribute(sName);else return this._htmlAttributes[sName];};_p._setHtmlAttribute=function(sName,sValue){this._htmlAttributes[sName]=sValue;if(this._created)
this._element.setAttribute(sName,sValue);};_p._removeHtmlAttribute=function(sName){delete this._htmlAttributes[sName];if(this._created)
this._element.removeAttribute(sName);};_p.setId=function(sId){BiEventTarget.prototype.setId.call(this,sId);this.setHtmlProperty("id",sId);};_p.setForeColor=function(sForeColor){this.setStyleProperty("color",sForeColor);};_p.getForeColor=function(){return this.getStyleProperty("color");};_p.setBackColor=function(sBackColor){this.setStyleProperty("backgroundColor",sBackColor);};_p.getBackColor=function(){return this.getStyleProperty("backgroundColor");};_p.setVisible=function(bVisible){if(!bVisible&&this.getContainsFocus())
this.setFocused(false);this._visible=bVisible;this.setStyleProperty("visibility",bVisible?BiComponent.STYLE_INHERIT:BiComponent.STYLE_HIDDEN);if(this._lazyCreate&&!this._created&&bVisible){var p=this.getParent();if(p&&p._created)
this._addHtmlElementToParent(p,this.getNextSibling(),true);}};BiComponent.addProperty(BiComponent.STYLE_VISIBLE,Function.READ);_p.getIsVisible=function(){if(!this.getVisible()||!this.getCreated())return false;var el=this._element;BiComponent.flushLayoutComponent(this);if(el.offsetHeight==0||el.offsetWidth==0){return false;}
if(BiBrowserCheck.ie){while(el.tagName!="BODY"){if(el.currentStyle.visibility==BiComponent.STYLE_INHERIT){el=el.parentNode;continue;}
return el.currentStyle.visibility==BiComponent.STYLE_VISIBLE;}}else{var compStyle=this._document.defaultView.getComputedStyle(el,null);return compStyle.visibility!=BiComponent.STYLE_HIDDEN&&compStyle.display!=BiComponent.STYLE_NONE;}
return true;};_p.setOpacity=function(n){n=Math.max(0,Math.min(1,n));if(this._opacity!=n){this._opacity=n;if(BiBrowserCheck.ie){this.setStyleProperty("filter",this._getIeFilter());}else{if(n==1)
this.removeStyleProperty("MozOpacity");else this.setStyleProperty("MozOpacity",n);}}};BiComponent.prototype.getOpacity=function(){return this._opacity;};_p._getIeFilter=function(){if(this._opacity==1){return String.EMPTY;}
return"Alpha(Opacity="+Math.round(this._opacity*100)+")";};_p.setLeft=function(nLeft){if(this._left!=nLeft){this._left=nLeft;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setRight=function(nRight){if(this._right!=nRight){this._right=nRight;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setTop=function(nTop){if(this._top!=nTop){this._top=nTop;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setBottom=function(nBottom){if(this._bottom!=nBottom){this._bottom=nBottom;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setWidth=function(nWidth){if(this._width!=nWidth){this._width=nWidth;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setHeight=function(nHeight){if(this._height!=nHeight){this._height=nHeight;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setSize=function(nWidth,nHeight){if(this._width!=nWidth||this._height!=nHeight){this._width=nWidth;this._height=nHeight;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.setLocation=function(nLeft,nTop){if(this._left!=nLeft||this._top!=nTop){this._left=nLeft;this._top=nTop;this.invalidateParentLayout(BiComponent.STRING_SIZE);}};_p.invalidateParentLayout=function(sHint){this._invalidBoundaries=true;var p=this._parent;if(p)
p.invalidateChild(this,sHint);};_p.invalidateLayout=function(){this._invalidLayout=true;if(this._created)
BiComponent.enqueueLayout(this);};_p.invalidateChild=function(oChild,sHint){if(sHint==null||sHint==BiComponent.STRING_SIZE)
this._invalidateChild(oChild,sHint);};_p._invalidateChild=function(oChild,sHint){if(!oChild._invalidBoundaries){oChild._invalidBoundaries=true;oChild.invalidateParentLayout(sHint);}
oChild._invalidBoundaries=true;if(oChild._created)
BiComponent.enqueueLayout(oChild);};_p._invalidBoundaries=true;BiComponent._layoutQueue=new BiHashTable;BiComponent.enqueueLayout=function(c){var hc=c.toHashCode();if(this._layoutQueue.hasKey(hc))
return;this._layoutQueue.add(hc,c);};BiComponent.flushLayoutQueue=function(){if(this._inFlushLayoutQueue||this._inFlushLayoutComponent)
return;this._inFlushLayoutQueue=true;var ks,vs;while(!this._layoutQueue.isEmpty()){ks=this._layoutQueue.getKeys();vs=this._layoutQueue.getValues();for(var i=0;i<ks.length;i++){this.flushLayoutComponent(vs[i]);this._layoutQueue.remove(ks[i]);}}
this._inFlushLayoutQueue=false;};BiComponent.flushLayoutComponent=function(c){if(this._inFlushLayoutComponent)
return;this._inFlushLayoutComponent=true;var hc;var cs=[];while(c){hc=c.toHashCode();if(this._layoutQueue.hasKey(hc))
cs.push(c);c=c._parent;}
for(var i=cs.length-1;i>=0;i--){c=cs[i];hc=c.toHashCode();this._layoutQueue.remove(hc);if(c.getDisposed())
continue;if(c._invalidBoundaries)
c.layoutComponent();if(c._invalidLayout)
c.layoutAllChildren();}
this._inFlushLayoutComponent=false;};_p.layoutComponent=function(){var p;if(this._created&&(p=this._parent)){p.layoutChild(this);}};_p.layoutChild=function(oChild){var sizeChanged=this._layoutChild(oChild);if(sizeChanged){oChild.invalidateLayout();}};_p.layoutAllChildren=function(){var cs=this._children;var l=cs.length;for(var i=0;i<l;i++){cs[i].layoutComponent();}
this._invalidLayout=false;};_p._layoutChild=function(oChild){var x,y,w,h;var cw,ch;if(oChild._left!=null){x=oChild._left;if(oChild._right!=null){cw=this.getClientWidth();w=cw-oChild._left-oChild._right;}else if(oChild._width!=null){w=oChild._width;}}
else if(oChild._right!=null){if(oChild._width!=null)
w=oChild._width;else{oChild.removeStyleProperty("width");if(BiBrowserCheck.ie&&oChild._measuredHeight==0&&oChild._created){oChild._element.runtimeStyle.display=String.EMPTY;}
w=oChild.getWidth();}
cw=this.getClientWidth();x=cw-w-oChild._right;}
else if(oChild._width!=null){w=oChild._width;}
if(oChild._top!=null){y=oChild._top;if(oChild._bottom!=null){ch=this.getClientHeight();h=ch-oChild._top-oChild._bottom;}
else if(oChild._height!=null){h=oChild._height;}}
else if(oChild._bottom!=null){if(oChild._height!=null)
h=oChild._height;else{oChild.removeStyleProperty("height");if(BiBrowserCheck.ie&&oChild._measuredHeight==0&&oChild._created){oChild._element.runtimeStyle.display=String.EMPTY;}
h=oChild.getHeight();}
ch=this.getClientHeight();y=ch-h-oChild._bottom;}
else if(oChild._height!=null){h=oChild._height;}
return this._layoutChild2(oChild,x,y,w,h);};_p._layoutChild2=function(c,x,y,w,h,bInvalidate){var wChanged=false;var hChanged=false;var componentMoved=(c._clientLeft!=null&&x!=c._clientLeft)
||(c._clientTop!=null&&y!=c._clientTop);c._clientLeft=x;c._clientTop=y;c._invalidBoundaries=false;if(w!=null){wChanged=w!=c._measuredWidth;c._measuredWidth=w;}
if(h!=null){hChanged=h!=c._measuredHeight;c._measuredHeight=h;}
if(BiBrowserCheck.ie){if(x!=null)c.setStyleProperty(BiComponent.STRING_LEFT,x);if(y!=null)c.setStyleProperty(BiComponent.STRING_TOP,y);if(w!=null)c.setStyleProperty(BiComponent.STRING_WIDTH,w);if(h!=null)c.setStyleProperty(BiComponent.STRING_HEIGHT,h);if(hChanged&&c._created)
c._element.runtimeStyle.display=h==0?BiComponent.STYLE_NONE:String.EMPTY;}
else{if(x!=null)c.setStyleProperty(BiComponent.STRING_LEFT,x+BiComponent.STRING_PX);if(y!=null)c.setStyleProperty(BiComponent.STRING_TOP,y+BiComponent.STRING_PX);if(w!=null)c.setStyleProperty(BiComponent.STRING_WIDTH,w+BiComponent.STRING_PX);if(h!=null)c.setStyleProperty(BiComponent.STRING_HEIGHT,h+BiComponent.STRING_PX);}
if(componentMoved){c.dispatchEvent(BiComponent.STRING_MOVE);}
if(wChanged||hChanged){c.dispatchEvent(BiComponent.STRING_RESIZE);if(bInvalidate){c.invalidateLayout();}
return true;}
return false;};_p.getLeft=function(){if(this._created&&this._parent){BiComponent.flushLayoutComponent(this);return this._element.offsetLeft;}
return this._left;};_p.getRight=function(){if(this._created&&this._parent){BiComponent.flushLayoutComponent(this);return this._parent.getClientWidth()-this.getLeft()-this.getWidth();}
return this._right;};_p.getTop=function(){if(this._created&&this._parent){BiComponent.flushLayoutComponent(this);return this._element.offsetTop;}
return this._top;};_p.getBottom=function(){if(this._created&&this._parent){BiComponent.flushLayoutComponent(this);return this._parent.getClientHeight()-this.getTop()-this.getHeight();}
return this._bottom;};_p.getWidth=function(){if(this._created){BiComponent.flushLayoutComponent(this);if(BiBrowserCheck.moz){if(this._element.style.width==String.EMPTY){var ps=this._element.parentNode.style;var w=ps.width;var h=ps.height;ps.width=ps.height="9999999px";var res=this._element.offsetWidth;ps.width=w;ps.height=h;return res;}
return this._element.offsetWidth;}
else{return this._element.offsetWidth;}}
return this._width;};_p.getHeight=function(){if(this._created){BiComponent.flushLayoutComponent(this);if(BiBrowserCheck.moz){if(this._element.style.height==String.EMPTY){var ps=this._element.parentNode.style;var w=ps.width;var h=ps.height;ps.width=ps.height="9999999px";var res=this._element.offsetHeight;ps.width=w;ps.height=h;return res;}
return this._element.offsetHeight;}
else{return this._element.offsetHeight;}}
return this._height;};_p.getClientWidth=function(){if(this._created){BiComponent.flushLayoutComponent(this);return this._element.clientWidth;}
throw new Error("Visual property on non created component");};_p.getClientHeight=function(){if(this._created){BiComponent.flushLayoutComponent(this);return this._element.clientHeight;}
throw new Error("Visual property on non created component");};_p.getIsEnabled=function(){if(!this.getEnabled()){return false;}
if(this._created){if(BiBrowserCheck.ie){return!this._element.isDisabled;}else{var el=this._element;while(el){if(el.nodeType==1&&el.hasAttribute("disabled")){return false;}
el=el.parentNode;}}
return true;}else{var p=this.getParent();if(p)
return p.getIsEnabled();return true;}};BiComponent.prototype.getCreated=function(){return this._created;};BiComponent._createEvent=new BiEvent("create");_p._create=function(oDocument){this._document=oDocument||document;var el=this._element=this._document.createElement(this._tagName);el._biComponent=this;this._setHtmlProperties();this._setCssProperties();if(BiBrowserCheck.ie){el.onscroll=BiComponent.__oninlineevent;}
else{this._setHtmlAttributes();el.onscroll=el.onfocus=BiComponent.__oninlineevent;}};_p._createChildren=function(){var cs=this._children;var l=cs.length;for(var i=0;i<l;i++){cs[i]._addHtmlElementToParent(this,null);}};_p._setCssProperties=function(){var es=this._element.style;var sp=this._style;for(var p in sp){es[p]=sp[p];}};_p._setHtmlProperties=function(){var el=this._element;var hp=this._htmlProperties;for(var p in hp){el[p]=hp[p];}};_p._setHtmlAttributes=function(){var el=this._element;var ha=this._htmlAttributes;for(var n in ha)
el.setAttribute(n,ha[n]);};_p._addHtmlElementToParent=function(oParent,oBefore){if(this._lazyCreate&&!this.getVisible())
return;if(!this._created)
this._create(oParent._document);var beforeElement=oBefore?oBefore._element:null;while(beforeElement&&beforeElement.parentNode!=oParent._element)
beforeElement=beforeElement.parentNode;if(beforeElement)
oParent._element.insertBefore(this._element,beforeElement);else oParent._element.appendChild(this._element);this._created=true;this._createChildren();this.invalidateParentLayout();if(oParent.getHtmlProperty("disabled")&&!this.getHtmlProperty("disabled")){this._setEnabled(false);}
this.dispatchEvent(BiComponent._createEvent);};_p._removeHtmlElement=function(oChild){oChild._removeHtmlElementFromParent(this);};_p._removeHtmlElementFromParent=function(oParent){if(this._created&&(oParent&&oParent._created&&oParent._element)&&!(application&&application._disposed)){oParent._element.removeChild(this._element);}};_p.dispose=function(){if(this._disposed)return;BiComponent._layoutQueue.remove(this.toHashCode());BiEventTarget.prototype.dispose.call(this);if(this._parent){try{this._parent.remove(this);}
catch(e){}
delete this._parent;}
var el=this._element;if(el){el.onscroll=el.onfocus=el.onlosecapture=null;el._biComponent=null;if(BiBrowserCheck.ie){el.removeAttribute("_biComponent");}
el=null;}
this.disposeFields("_children","_border","__oninlineevent","_document","_style","_htmlProperties","_htmlAttributes","_created","_element","_cssClassName")};_p.addXmlNode=function(oNode,oParser){if(oNode.nodeType==1){var c=oParser.fromNode(oNode);if(c instanceof BiComponent)
this.add(c);}};BiComponent.invalidateAll=function(){var els;if(BiBrowserCheck.ie)
els=document.all;else els=document.getElementsByTagName("*");var l=els.length;var cs=[];var i;for(i=0;i<l;i++){var el=els[i];if(el._biComponent&&el._biComponent instanceof BiComponent&&!el._biComponent.getDisposed()){cs.push(el._biComponent);}}
l=cs.length;for(i=0;i<l;i++){cs[i]._invalidLayout=true;cs[i]._invalidBoundaries=true;BiComponent.enqueueLayout(cs[i]);}};BiComponent.__oninlineevent=_p.setRightToLeft=_p.setCssClassName=_p.getContainsFocus=Function.EMPTY;function BiInlineComponent(sId)
{if(_biInPrototype)return;BiComponent.call(this);this._inlineId=sId;this.setCssClassName("bi-inline-component");}
_p=_biExtend(BiInlineComponent,BiComponent,"BiInlineComponent");_p._create=function(oDocument){this._document=oDocument||document;var el=this._element=this._document.getElementById(this._inlineId);if(!el)
throw new Error("BiInlineComponent, could not find element in page");el._biComponent=this;this._setHtmlProperties();this._setCssProperties();if(BiBrowserCheck.ie)
el.onscroll=el.onresize=BiComponent.__oninlineevent;else {this._setHtmlAttributes();el.onscroll=el.onfocus=BiComponent.__oninlineevent;}};_p._addHtmlElementToParent=function(oParent,oBefore,bLayout){if(bLayout==null)
bLayout=true;if(!this._created)
this._create(oParent._document);this._created=true;this._createChildren();this.invalidateParentLayout();this.dispatchEvent(BiComponent._createEvent);};_p._removeHtmlElementFromParent=function(oParent)
{};BiInlineComponent.prototype.getInlineId=function(){return this._inlineId;};BiInlineComponent.prototype.setInlineId=function(v){this._inlineId=v;};_p.layoutComponent=function(){BiComponent.prototype.layoutComponent.call(this);this.layoutAllChildren();};function BiApplicationWindow(){if(_biInPrototype)return;BiComponent.call(this);this.removeHtmlProperty("className");this.removeHtmlProperty("id");this.setRightToLeft(false);this._shownDialogs=[];this._commands={};this._eventManager=new BiEventManager;this._focusManager=new BiFocusManager;this._glassPane=new BiComponent;this._glassPane.setCssClassName("bi-glass-pane");this._glassPane.setLocation(0,0);this._glassPane.setRight(0);this._glassPane.setBottom(0);this.addEventListener("keydown",this._ondefaultbuttonkeydown);this.addEventListener("keydown",this._onkeyevent);this.addEventListener("keypress",this._onkeyevent);if(BiBrowserCheck.moz){this._canSelect=true;}};_p=_biExtend(BiApplicationWindow,BiComponent,"BiApplicationWindow");_p._diff=null;_p._insets=null;_p._insetLeft=4;_p._insetRight=4;_p._insetTop=30;_p._insetBottom=4;_p._lastActive=null;_p._activeComponent=null;_p._globalCursor=null;_p._moveTo=function(nLeft,nTop){try{this._window.moveTo(nLeft,nTop);}
catch(ex){}}
_p._resizeTo=function(nWidth,nHeight){try{this._window.resizeTo(nWidth,nHeight);}
catch(ex){}}
_p.setLeft=function(nLeft){this._moveTo(nLeft,this.getTop());};_p.setRight=function(nRight){this.setLeft(this._window.screen.width-this.getWidth()-nRight);};_p.getScreenLeft=_p.getLeft=function(){if(BiBrowserCheck.ie)
return this._window.screenLeft-this._getInsets().left;else return this._window.screenX;};_p.getRight=function(){return this._window.screen.width-this.getLeft()-this.getWidth();};_p.setTop=function(nTop){this._moveTo(this.getLeft(),nTop);};_p.setBottom=function(nBottom){this.setTop(this._window.screen.height-this.getHeight()-nBottom);};_p.setLocation=function(nLeft,nTop){this._moveTo(nLeft,nTop);};_p.getScreenTop=_p.getTop=function()
{if(BiBrowserCheck.ie)
return this._window.screenTop-this._getInsets().top;else return this._window.screenY;};_p.getBottom=function(){return this._window.screen.height-this.getTop()-this.getHeight();};_p.setWidth=function(nWidth){if(BiBrowserCheck.ie)
this._resizeTo(nWidth,this.getHeight());else this._window.outerWidth=nWidth;};_p.getWidth=function(){if(BiBrowserCheck.ie)
return this._getSize().width;else return this._window.outerWidth;};_p.setHeight=function(nHeight)
{if(BiBrowserCheck.ie)
this._resizeTo(this.getWidth(),nHeight);else this._window.outerHeight=nHeight;};_p.setSize=function(nWidth,nHeight){this._resizeTo(nWidth,nHeight);};_p.getHeight=function(){if(BiBrowserCheck.ie)
return this._getSize().height;else return this._window.outerHeight;};_p.getInsetLeft=function(){return this._getInsets().left+BiComponent.prototype.getInsetLeft.call(this);};_p.getInsetRight=function(){return this.getWidth()-this.getInsetLeft()-this.getClientWidth();};_p.getInsetTop=function(){return this._getInsets().top+BiComponent.prototype.getInsetTop.call(this);};_p.getInsetBottom=function(){return this.getHeight()-this.getInsetTop()-this.getClientHeight();};_p.getClientLeft=function(){return-this.getInsetLeft();};_p.getClientTop=function(){return-this.getInsetTop();};_p._getScreenClientArea=function()
{if(BiBrowserCheck.ie)
return{left:this._window.screenLeft,top:this._window.screenTop,width:this._element.clientWidth,height:this._element.clientHeight};else {var bo=this._document.getBoxObjectFor(this._document.documentElement);return{left:bo.screenX,top:bo.screenY,width:this._element.clientWidth,height:this._element.clientHeight};}};_p.setZIndex=_p.getZIndex=_p.setVisible=_p.getVisible=function(){throw new Error("Not supported");};_p.getTopLevelComponent=function(){return this;};_p.getIsVisible=function(){return true;};_p.getCaption=function(){return this._window.document.title;};_p.setCaption=function(sTitle){this._window.document.title=sTitle;};_p.getFullScreen=function(){throw new Error("Not yet implemented");};_p.getResizable=function(){throw new Error("Not yet implemented");};_p.close=function(){this._window.close();};_p.print=function(){this._window.print();};_p.isFocusRoot=function(){return true;};_p.getFocusRoot=function(){return this;};_p.getActiveComponent=function(){if(this._activeComponent&&this._activeComponent.getDisposed())
this._activeComponent=null;return this._activeComponent;};BiApplicationWindow.prototype.getAcceptButton=function(){return this._acceptButton;};BiApplicationWindow.prototype.setAcceptButton=function(v){this._acceptButton=v;};BiApplicationWindow.prototype.getCancelButton=function(){return this._cancelButton;};BiApplicationWindow.prototype.setCancelButton=function(v){this._cancelButton=v;};_p.addCommand=function(c)
{if(c.getOwnerWindow())
c.getOwnerWindow().removeCommand(c);this._commands[c.toHashCode()]=c;c._ownerWindow=this;};_p.removeCommand=function(c)
{delete this._commands[c.toHashCode()];c._ownerWindow=null;};_p.remove=function(c)
{if(c instanceof BiDialog&&c.getIsVisible())
c.setVisible(false);BiComponent.prototype.remove.call(this,c);};_p.updateGlassPane=function(oDialog,bVisible){this._shownDialogs.remove(oDialog);if(bVisible){this.add(this._glassPane,oDialog);this._glassPane.setZIndex(oDialog.getZIndex());if(BiBrowserCheck.moz)
{oDialog.setZIndex(oDialog.getZIndex()+1);}
this._shownDialogs.push(oDialog);}
else{if(this._shownDialogs.length==0){this.remove(this._glassPane);var c=this.getActiveComponent();if(c&&c.getCanFocus()){c.setFocused(true);}}
else{var d=this._shownDialogs[this._shownDialogs.length-1];this._glassPane.setZIndex(d.getZIndex());d.setActive(true);}}};_p.setGlassPaneVisible=function(b)
{var gp=this._glassPane;if(b){this.add(gp);var cs=this._children;var max=0;for(var i=0;i<cs.length;i++){max=Math.max(max,cs[i].getZIndex());}
gp.setZIndex(max+1);gp.setTabIndex(1);gp.setHideFocus(true);gp.getFocusRoot=function(){return this;};gp.isFocusRoot=function(){return true;};gp.setFocused(true);}
else{if(this._shownDialogs.length==0){if(gp._parent==this){this.remove(gp);gp.setZIndex(0);gp.setTabIndex(-1);gp.getFocusRoot=function(){return this._parent;};gp.isFocusRoot=function(){return false;};}}}};_p.getGlassPaneVisible=function()
{return this._glassPane.getVisible();};BiApplicationWindow.prototype.getGlobalCursor=function(){return this._globalCursor;};_p.setGlobalCursor=function(sCursor){this._globalCursor=sCursor;var tm=application.getThemeManager();if(sCursor==null||sCursor==""){tm.removeCssRule("*");tm.removeCssRule("");}else{tm.addCssRule("*","cursor:"+sCursor+" !important");}};_p.getAllowBrowserContextMenu=function()
{return this._eventManager.getAllowBrowserContextMenu();};_p.setAllowBrowserContextMenu=function(b)
{return this._eventManager.setAllowBrowserContextMenu(b);};_p._create=function(oWindow){this._window=oWindow||window;this._document=this._window.document;var el=this._element=this._document.body;el._biComponent=this;this._window._biComponent=this;this._document.documentElement._biComponent=this;this._eventManager.attachToWindow(this._window);this._setHtmlProperties();this._setCssProperties();if(BiBrowserCheck.ie)
el.onscroll=BiComponent.__oninlineevent;else {this._setHtmlAttributes();el.onscroll=el.onfocus=BiComponent.__oninlineevent;}
this._created=true;var cs=this._children;var l=cs.length;for(var i=0;i<l;i++){cs[i]._addHtmlElementToParent(this);}};_p.dispose=function()
{if(this.getDisposed())return;BiComponent.prototype.dispose.call(this);if(this._cursorStyleEl)
{this._cursorStyleEl.disabled=true;this._cursorStyleEl.cssText="";delete this._cursorStyleEl;}
this._focusManager.dispose();delete this._focusManager;this._eventManager.dispose();delete this._eventManager;for(var i=this._shownDialogs.length-1;i>=0;i--)
this._shownDialogs[i]=null;delete this._shownDialogs;for(var c in this._commands)
delete this._commands[c];delete this._commands;this._window.document.documentElement._biComponent=null;this._window._biComponent=null;delete this._window;this._glassPane.dispose();delete this._glassPane;};_p._invalidBoundaries=false;_p._onresize=function(e)
{var b=this._window.document.body;var newW=b.offsetWidth;var newH=b.offsetHeight;if(this._lastResizeW!=newW||this._lastResizeH!=newH)
{this._lastResizeW=newW;this._lastResizeH=newH;(new BiPopupManager).hideAutoHiding();this.invalidateLayout();this.dispatchEvent("resize");}};_p._onkeyevent=function(e)
{this._focusManager.processKeyEvent(this,e);};_p._ondefaultbuttonkeydown=function(e)
{var c;for(var hc in this._commands)
{c=this._commands[hc];if(c.getEnabled()&&c.matchesKeyboardEvent(e))
{if(!c.execute())
e.preventDefault();}}
var t=e.getTarget();switch(e.getKeyCode())
{case BiKeyboardEvent.ENTER:if(this._acceptButton&&this._acceptButton.getEnabled()&&!t._acceptsEnter&&!e.getAltKey())
{this._acceptButton.dispatchEvent("action");if(this._acceptButton.getCommand())
{this._acceptButton.getCommand().execute()}}
break;case BiKeyboardEvent.ESC:if(this._cancelButton&&this._cancelButton.getEnabled()&&!t._acceptsEsc)
{this._cancelButton.dispatchEvent("action");if(this._cancelButton.getCommand())
{this._cancelButton.getCommand().execute()}}
break;}};_p._getSize=function(){var oldInnerSize=this._getInnerSize();if(this._diff==null)
this._diff={width:Number(this._insetLeft)+Number(this._insetRight),height:Number(this._insetTop)+Number(this._insetBottom)};this._resizeTo(oldInnerSize.width+this._diff.width,oldInnerSize.height+this._diff.height);var newInnerSize=this._getInnerSize();var diff={width:oldInnerSize.width-newInnerSize.width+this._diff.width,height:oldInnerSize.height-newInnerSize.height+this._diff.height};this._resizeTo(oldInnerSize.width+diff.width,oldInnerSize.height+diff.height);this._diff=diff;return{width:oldInnerSize.width+diff.width,height:oldInnerSize.height+diff.height};};_p._getInnerSize=function(){return{width:this.getClientWidth(),height:this.getClientHeight()};};_p._getInsets=function(){if(BiBrowserCheck.ie)
{var oldScreenLeft=this._window.screenLeft;var oldScreenTop=this._window.screenTop;if(this._insets==null)
this._insets={left:Number(this._insetLeft),top:Number(this._insetTop)};this._moveTo(oldScreenLeft-this._insets.left,oldScreenTop-this._insets.top);var newScreenLeft=this._window.screenLeft;var newScreenTop=this._window.screenTop;var res={left:newScreenLeft-oldScreenLeft+this._insets.left,top:newScreenTop-oldScreenTop+this._insets.top};this._moveTo(oldScreenLeft-res.left,oldScreenTop-res.top);return this._insets=res;}
else {var bo=this._document.getBoxObjectFor(this._document.documentElement);return{left:bo.screenX-this._window.screenX,top:bo.screenY-this._window.screenY};}};function BiFont(nSize,sName){if(_biInPrototype)return;BiObject.call(this);if(nSize!=null)this._size=nSize;if(sName!=null)this._name=sName;}
_p=_biExtend(BiFont,BiObject,"BiFont");BiFont.prototype.getSize=function(){return this._size;};BiFont.prototype.setSize=function(v){this._size=v;};BiFont.prototype.getBold=function(){return this._bold;};BiFont.prototype.setBold=function(v){this._bold=v;};BiFont.prototype.getItalic=function(){return this._italic;};BiFont.prototype.setItalic=function(v){this._italic=v;};BiFont.prototype.getUnderline=function(){return this._underline;};BiFont.prototype.setUnderline=function(v){this._underline=v;};BiFont.prototype.getStrikeout=function(){return this._strikeout;};BiFont.prototype.setStrikeout=function(v){this._strikeout=v;};BiFont.prototype.getName=function(){return this._name;};BiFont.prototype.setName=function(v){this._name=v;};_p.paintFont=function(oComponent)
{if(this._name)
oComponent.setStyleProperty("fontFamily",this._name);if(this._size!=null)
oComponent.setStyleProperty("fontSize",this._size+"px");if(this._bold!=null)
oComponent.setStyleProperty("fontWeight",this._bold?"bold":"normal");if(this._italic!=null)
oComponent.setStyleProperty("fontStyle",this._italic?"italic":"normal");var td=null;if(this._underline==false&&this._strikeout==false)
td="none";else if(this._underline!=null||this._strikeout!=null)
{td=(this._underline?"underline":"")+(this._strikeout?" line-through":"");}
if(td!=null)
oComponent.setStyleProperty("textDecoration",td);};_p.removeFont=function(oComponent){oComponent.removeStyleProperty("fontFamily");oComponent.removeStyleProperty("fontSize");oComponent.removeStyleProperty("fontWeight");oComponent.removeStyleProperty("fontStyle");oComponent.removeStyleProperty("textDecoration");};BiFont.fromString=function(s)
{var f=new BiFont;var parts=s.split(/\s+/);var nameSb=[];var part;for(var i=0;i<parts.length;i++)
{part=parts[i];switch(part)
{case"bold":f.setBold(true);break;case"italic":f.setItalic(true);break;case"underline":f.setUnderline(true);break;case"strikeout":f.setStrikeout(true);break;default:var n=parseFloat(part);if(n==part||part.indexOf("px")!= -1)
f.setSize(n);else nameSb.push(part);break;}}
if(nameSb.length>0)
f.setName(nameSb.join(" "));return f;};bindows={loadGaugeIntoDiv:function(sUri,divId){if(!BiBrowserCheck.ie&&!BiBrowserCheck.moz)
return null;if(!application._window){application._adfPath=new BiUri(application._uri,"./");application._window=new BiApplicationWindow;application._window._onkeyevent=Function.EMPTY;application._window._create();}
var xmlHttp=this._syncRequest(sUri);var s=xmlHttp.responseText.replace(/<\?xml[^\?]*\?>/,"");var doc=xmlHttp.responseXML;doc.loadXML(s);var gaugeClass=BiXmlResourceParser.getClassFromDocument(doc);var gauge=new gaugeClass();gauge.setVisible(false);var c=new BiInlineComponent(divId);c.add(gauge);application._window.add(c);BiTimer.callOnce(function(){gauge.setVisible(true);})
var g={__gauge:gauge}
var ids=gauge._xmlResourceParser._componentsById.getKeys();for(var i=0;i<ids.length;i++)
g[ids[i]]=gauge.getComponentById(ids[i]);return g;},_syncRequest:function(sUri){var xmlHttp=new BiXmlHttp;xmlHttp.open('GET',sUri,false);xmlHttp.setRequestHeader("Accept-Encoding","gzip, deflate");xmlHttp.send(null);return xmlHttp;}};(function()
{var packagedName="bindows_gauges.js";var scripts=document.getElementsByTagName("SCRIPT");var script;for(var i=0;i<scripts.length;i++){if(scripts[i].src.indexOf(packagedName)!= -1){script=scripts[i];break;}}
var s=script.src;var base=s.substring(0,s.indexOf(packagedName));var js="";if(BiBrowserCheck.ie)js=bindows._syncRequest(base+"gauge2.ie.js").responseText;if(BiBrowserCheck.moz)js=bindows._syncRequest(base+"gauge2.moz.js").responseText;if(window.execScript)window.execScript(js);else window.eval(js);})();if(BiBrowserCheck.ie){var ss=document.createStyleSheet();ss.addRule("v\\:*","behavior:	url(#default#VML);");}