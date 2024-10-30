/*
 * jQuery JSON Plugin
 * version: 2.1 (2009-08-14)
 *
 * This document is licensed as free software under the terms of the
 * MIT License: http://www.opensource.org/licenses/mit-license.php
 *
 * Brantley Harris wrote this plugin. It is based somewhat on the JSON.org 
 * website's http://www.json.org/json2.js, which proclaims:
 * "NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.", a sentiment that
 * I uphold.
 *
 * It is also influenced heavily by MochiKit's serializeJSON, which is 
 * copyrighted 2005 by Bob Ippolito.
 */
 
(function($) {
    /** jQuery.toJSON( json-serializble )
        Converts the given argument into a JSON respresentation.

        If an object has a "toJSON" function, that will be used to get the representation.
        Non-integer/string keys are skipped in the object, as are keys that point to a function.

        json-serializble:
            The *thing* to be converted.
     **/
    $.toJSON = function(o)
    {
        if (typeof(JSON) == 'object' && JSON.stringify)
            return JSON.stringify(o);
        
        var type = typeof(o);
    
        if (o === null)
            return "null";
    
        if (type == "undefined")
            return undefined;
        
        if (type == "number" || type == "boolean")
            return o + "";
    
        if (type == "string")
            return $.quoteString(o);
    
        if (type == 'object')
        {
            if (typeof o.toJSON == "function") 
                return $.toJSON( o.toJSON() );
            
            if (o.constructor === Date)
            {
                var month = o.getUTCMonth() + 1;
                if (month < 10) month = '0' + month;

                var day = o.getUTCDate();
                if (day < 10) day = '0' + day;

                var year = o.getUTCFullYear();
                
                var hours = o.getUTCHours();
                if (hours < 10) hours = '0' + hours;
                
                var minutes = o.getUTCMinutes();
                if (minutes < 10) minutes = '0' + minutes;
                
                var seconds = o.getUTCSeconds();
                if (seconds < 10) seconds = '0' + seconds;
                
                var milli = o.getUTCMilliseconds();
                if (milli < 100) milli = '0' + milli;
                if (milli < 10) milli = '0' + milli;

                return '"' + year + '-' + month + '-' + day + 'T' +
                             hours + ':' + minutes + ':' + seconds + 
                             '.' + milli + 'Z"'; 
            }

            if (o.constructor === Array) 
            {
                var ret = [];
                for (var i = 0; i < o.length; i++)
                    ret.push( $.toJSON(o[i]) || "null" );

                return "[" + ret.join(",") + "]";
            }
        
            var pairs = [];
            for (var k in o) {
                var name;
                var type = typeof k;

                if (type == "number")
                    name = '"' + k + '"';
                else if (type == "string")
                    name = $.quoteString(k);
                else
                    continue;  //skip non-string or number keys
            
                if (typeof o[k] == "function") 
                    continue;  //skip pairs where the value is a function.
            
                var val = $.toJSON(o[k]);
            
                pairs.push(name + ":" + val);
            }

            return "{" + pairs.join(", ") + "}";
        }
    };

    /** jQuery.evalJSON(src)
        Evaluates a given piece of json source.
     **/
    $.evalJSON = function(src)
    {
        if (typeof(JSON) == 'object' && JSON.parse)
            return JSON.parse(src);
        return eval("(" + src + ")");
    };
    
    /** jQuery.secureEvalJSON(src)
        Evals JSON in a way that is *more* secure.
    **/
    $.secureEvalJSON = function(src)
    {
        if (typeof(JSON) == 'object' && JSON.parse)
            return JSON.parse(src);
        
        var filtered = src;
        filtered = filtered.replace(/\\["\\\/bfnrtu]/g, '@');
        filtered = filtered.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
        filtered = filtered.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
        
        if (/^[\],:{}\s]*$/.test(filtered))
            return eval("(" + src + ")");
        else
            throw new SyntaxError("Error parsing JSON, source is not valid.");
    };

    /** jQuery.quoteString(string)
        Returns a string-repr of a string, escaping quotes intelligently.  
        Mostly a support function for toJSON.
    
        Examples:
            >>> jQuery.quoteString("apple")
            "apple"
        
            >>> jQuery.quoteString('"Where are we going?", she asked.')
            "\"Where are we going?\", she asked."
     **/
    $.quoteString = function(string)
    {
        if (string.match(_escapeable))
        {
            return '"' + string.replace(_escapeable, function (a) 
            {
                var c = _meta[a];
                if (typeof c === 'string') return c;
                c = a.charCodeAt();
                return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
            }) + '"';
        }
        return '"' + string + '"';
    };
    
    var _escapeable = /["\\\x00-\x1f\x7f-\x9f]/g;
    
    var _meta = {
        '\b': '\\b',
        '\t': '\\t',
        '\n': '\\n',
        '\f': '\\f',
        '\r': '\\r',
        '"' : '\\"',
        '\\': '\\\\'
    };
})(jQuery);

var Kontrol = new function()
   {
   this.capture = function(type,event) { this._preprocess(type,event); };
   this.critical = function(event) { this._preprocess('Critical',event); };
   this.debug = function(event) { this._preprocess('Debug',event); };
   this.error = function(event) { this._preprocess('Error',event); };
   this.exception = function(event) { this._preprocess('Exception',event); };
   this.feedback = function(message) { if(!this.isInitialized()) return; if(!this._variables.Enabled) return; this._internal('Feedback',message); };
   this.info = function(event) { this._preprocess('Info',event); };
   this.initialize = function(APIKey,Version)
      {
      if(Version == undefined) Version = '1.0';
      this._variables.APIKey = APIKey;
      this._variables.Version = Version;
      this._variables.Locale = navigator.language || navigator.userLanguage || 'en-US';
      this._setEnabled(true);
      this.setCookieEnabled(false);
      this.setOnErrorRedirect(false);
      this.setLocalLogEnabled(false);
      this.setRemoteLogEnabled(true);
      window.onerror = function(message,url,line,stack) { return Kontrol._error(message,url,line,stack); };
      };
   this.isInitialized = function() { return this._variables.APIKey != undefined; };
   this.setCookieEnabled = function(enabled) { this._variables.EnableCookie = enabled === true; };
   this.setLocalLogEnabled = function(enabled) { this._variables.EnableLocalLog = enabled === true; };
   this.setOnErrorRedirect = function(URL) { this._variables.OnErrorRedirect = URL; };
   this.setRemoteLogEnabled = function(enabled) { this._variables.EnableRemoteLog = enabled === true; };
   this.warning = function(event) { this._preprocess('Warning',event); };
   this._clone = function(o) { var a=new Object(); for(var n in o) { if(n.toUpperCase() == n) continue; var t=eval('typeof(o.'+n+');'); if(t!='function'&&t!='object'&&t!='undefined'&&t!='unknown') a[n]=o[n]; } return a; };
   this._error = function(message,url,line,stack) { if(!this.isInitialized()) return false; if(!this._variables.Enabled) return true; this._internal('Error',message,url,line,stack); if(this._variables.OnErrorRedirect) { this._setEnabled(false); try { window.location.replace(this._variables.OnErrorRedirect); } catch(e) { } } return (typeof e).toLowerCase() == 'string' || (typeof e).toLowerCase() == 'Error' || e instanceof Error; };
   this._internal = function(type,message,url,line,stack,context) { var trace = message + (url?'\n	at ' + url:''); if(line) trace += ' on line ' + line; if(this._variables.EnableLocalLog) this._localLog(type,trace,stack); if(this._variables.EnableRemoteLog) this._remoteLog(type,message,url,line,stack,context,trace); };
   this._localLog = function(type,trace,stack) { if(console) { var output = trace + (stack ? '\n' + stack : ''); if(console.log && type == 'Debug') console.log(output); else if(console.warn && type == 'Exception') console.warn(output); else if(console.error && (type == 'Critical' || type == 'Error')) console.error(output); } };
   this._parse = function(o,s){var a=new Array();for(var n in o){var v=o[n];var t=typeof(v);if(t!='function'&&t!='object'&&t!='undefined'&&v!=''){a[a.length]=(n+s+v);}}return a;};
   this._preprocess = function(type,event)
      {
      if(!this.isInitialized()) return;
      if(!this._variables.Enabled) return;
      if(event instanceof Error || event.message)
         {
         var message = event.message ? event.message : 'Unknown error';
         this._internal(type,message,window.location.href,undefined,event.stack,jQuery.toJSON(event));
         }
      else
         {
         var message = event;
         this._internal(type,message,window.location.href);
         }
      };
   this._remoteLog = function(type,message,url,line,stack,context,trace,opaque)
      {
      var doc = this._clone(document);
      if(!this._variables.EnableCookie) delete doc.cookie;

      var extra = new Object();
      extra.document = jQuery.toJSON(doc);
      extra.location = jQuery.toJSON(this._clone(window.location));
      extra.navigator = jQuery.toJSON(this._clone(window.navigator));
      extra.screen = jQuery.toJSON(this._clone(window.screen));
      /*extra.document = jQuery.toJSON(this._serialize('document','domain','referrer','title','URL'));
      extra.screen = jQuery.toJSON(this._serialize('screen','width','height','colorDepth'));*/
      
      var request = {};
      request.Time = Math.round(new Date().getTime()/1000);
      request.A = this._variables.APIKey;
      request.P = 5;
      request.L = this._variables.Locale;
      request.V = this._variables.Version;
      request.T = type;
      request.E = trace;
      request.X = jQuery.toJSON(extra);
      request.Z = 2;
      if(opaque != undefined) request.O = opaque;

      try
         {
         jQuery.post('http' + (window.location.protocol=='https:'?'s':'') + '://api.kontrol.io/rest/event-add.jsp',request);
         }
      catch(e) { }
      };
   this._serialize = function() { var a=arguments;var r=new Object();for(var i=1;i<a.length;++i){eval('r.'+a[i]+'='+a[0]+'.'+a[i]+';');}return r; };
   this._setEnabled = function(enabled) { this._variables.Enabled = enabled == true; };
   this._variables = new Object();
   };
