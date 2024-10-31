
if (typeof($) != 'function') {
	$ = function (a) { return document.getElementById(a);}
}

function Mediator(){}
function MyMediator(){}

Mediator.prototype = {
  ready: false,
  containers : {},
  
  init:  function()
  {
    this.ready = true;
  },

  show:  function(obj)
  {
    if(null !== obj)
    {
      obj.style.display = "block";
    }
  },

  hide:  function(obj)
  {
    if(null !== obj)
    {
      obj.style.display = "none";
    }
  },

  disable:  function(obj)
  {
    if(null !== obj)
    {
      obj.disabled = true;
    }
  },

  enable:  function(obj)
  {
    if(null !== obj)
    {
      obj.disabled = false;
    }
  },

  clear:  function(obj)
  {
    if(null !== obj)
    {
      if(obj.tagName == "SELECT")
      {
        obj.length=0;
      }
      else
      {
        if(obj.innerHTML)
        {
          obj.innerHTML = '';
        }
      }
    }
  },

  setColor:  function(obj, color)
  {
    if(null !== obj)
    {
      obj.style.color = color;
    }
  }
};

MyMediator.prototype = new Mediator();
MyMediator.prototype.objects = {};
MyMediator.prototype.tabs_count = 1;
MyMediator.prototype.showTab = function( tabId )
  {
    for( var i=1; i <= this.tabs_count ; i++  )
    {
      this.hide( this.objects[ 'tab_contents' + i ] );
      this.objects[ 'tab' + i ].className = null;
    }
    
    this.objects[ 'tab' + tabId ].className = 'selected';
    this.show( this.objects[ 'tab_contents' + tabId ] );
  };
  
m = new MyMediator();
m.tabs_count = 3;
m.objects = {
  'tab1': $( 'pushit_tab1' ),
  'tab2': $( 'pushit_tab2' ),
  'tab3': $( 'pushit_tab3' ),
  'tab_contents1': $( 'pushit_tab_contents1' ),
  'tab_contents2': $( 'pushit_tab_contents2' ),
  'tab_contents3': $( 'pushit_tab_contents3' )
};

var pushit_sack = new top.window.sack();

function ajax_busy() {
	return 4 != pushit_sack.xmlhttp.readyState && 0 != pushit_sack.xmlhttp.readyState;
}

function clear_error_messages()
{
  spans = document.getElementsByTagName('DIV');
  for ( var i = 0; i < spans.length; i++)
  {
    if( 'error' == spans[i].className )
    {
      spans[i].innerHTML = '&nbsp';
    }
  }
  return true;
}

function pushit_request( callback, param1, callback_error  )
{
  if (ajax_busy()) return false;

  clear_error_messages();
  
  pushit_sack.requestFile = top.window.pushit_base_uri + "/wp-content/plugins/pushit/backend.php";
  pushit_sack.method = "POST";
  pushit_sack.onError = function(){alert( pushit_sack.response ) };
  pushit_sack.onCompletion = function() {
        d = pushit_sack.response.split( '|' ); 
        if (1 == d[0] ) //all is good
        {
          can_close = true;
          if( callback )
          {
            param1 = d[1];
            can_close = callback( param1 );
          }
          if( can_close )
          {
            setTimeout( 'top.window.GB_hide()', 2000 ); 
          }
        }
        else //error
        {
        
          if( callback_error ) {
            callback_error( );
          }
          
          if( null != $( d[2] ) ) //try to show error message near a correspondent control
          {
            $( d[2] ).focus();
            //$( d[2] + '_msg' ).innerHTML = d[1];
          }
          //else //no? then show general error
          //{
            if( null != $( 'common_msg' ) )
            {
              $( 'common_msg' ).innerHTML = d[1];
            }
            else //ugh... Show an alert with error text
            {
              alert( d[1] );
            }
          //}
        }
      };
  pushit_sack.runAJAX();
  	
}

function show_message(t)
{
  var o = $( 'ok_message' );
  if( null == o )
  {
    return false;
  }
  o.innerHTML = t;
  o.style.display = 'block';
}

function hide_message()
{
  var o = $( 'ok_message' );
  if( null == o )
  {
    return false;
  }
  o.style.display = 'none';
}

function pushit_send_sms()
{
  var obj = $('pushit_sms_to_number');
  pushit_sack.vars = new Array()
  pushit_sack.setVar( 'to', ( 'text_clicked' == obj.className ? obj.value : '' )  )
  pushit_sack.setVar( 'id', top.window.post_id  )
  pushit_sack.setVar( 'action', 'sms' )

  callback = function()
  {
    show_message( 'Message has been sent.')
    return true
  };

  pushit_request( callback )
}

function pushit_send_email()
{
  var obj = $('pushit_email');
  pushit_sack.vars = new Array()
  pushit_sack.setVar( 'to', ( 'text_clicked' == obj.className ? obj.value : '' ) );
  var n = $('pushit_s_name');
  var e = $('pushit_s_email');
  if( n != null && e != null )
  {
    pushit_sack.setVar( 'from_name',  ( 'text_clicked' == n.className ? n.value : '' ) );
    pushit_sack.setVar( 'from_email', ( 'text_clicked' == e.className ? e.value : '' ) );
  }
  pushit_sack.setVar( 'id', top.window.post_id  );
  pushit_sack.setVar( 'action', 'email' );

  callback = function()
  {
    show_message( 'Email has been sent.'); 
    return true;
  };

  pushit_request( callback );
}
var redirect_to;
function pushit_login( action )
{
  redirect_to = action;
  form = $( 'pushit_loginForm' );
  pushit_sack.vars = new Array()
  pushit_sack.setVar("log", ( 'text_clicked' == form.log.className ? form.log.value : '' ) );
  pushit_sack.setVar("pwd", ( 'text_clicked' == form.pwd.className ? form.pwd.value : '' ) );
  pushit_sack.setVar("rememberme", form.rememberme.value);
  pushit_sack.setVar("action", 'login' );

  callback = function( username )
  {
    if( '' != top.window.pushit_redirectURL ) //redirect after login
    {
      top.document.location.href = top.window.pushit_redirectURL;
    }
    else
    {
      top.window.document.getElementById("pushit_username").innerHTML = username; //show logged user's name
      top.window.document.getElementById("pushit_login").style.display = 'none';
      top.window.document.getElementById("pushit_logged").style.display = 'block';
      
      if( null != redirect_to && '' != redirect_to )
      {
        document.location.href = top.window.pushit_base_uri + '/wp-content/plugins/pushit/pushit_pages.php?action=' + redirect_to;
        redirect_to = '';
        return false;
      }
    }
    return true;
  };

  pushit_request( callback );
}

function pushit_register()
{
  form = $( 'pushit_registerForm' );
  if( !form.user_license.checked )
  {
    $( 'common_msg' ).innerHTML = 'Please confirm user agreement';
    return false;
  }
  if( !form.mobilstart_user_license.checked )
  {
    $( 'common_msg' ).innerHTML = 'Please confirm Mobilstart user agreement';
    return false;
  }
  pushit_sack.vars = new Array()
  pushit_sack.setVar("user_login", ( 'text_clicked' == form.user_login.className ? form.user_login.value : '' ) );
  pushit_sack.setVar("user_mobile", ( 'text_clicked' == form.user_mobile.className ? form.user_mobile.value : '' ) );
  pushit_sack.setVar("user_email", ( 'text_clicked' == form.user_email.className ? form.user_email.value : '' ) );
  pushit_sack.setVar("user_name", ( 'text_clicked' == form.user_name.className ? form.user_name.value : '' ) );
  pushit_sack.setVar("user_surename", ( 'text_clicked' == form.user_surename.className ? form.user_surename.value : '' ) );
  pushit_sack.setVar("user_license", (form.user_license.checked ) );
  pushit_sack.setVar("mobilstart_user_license", (
form.mobilstart_user_license.checked ) ); 

  pushit_sack.setVar("action", 'register' );

  callback = function(blogname) {
    show_message( 'Thank you for registring at "' + blogname + '"!<br/><br/>Your password has been sent to your phone as an SMS.');
    return true;
  };
  
  pushit_request( callback );
}

function pushit_retrievePassword()
{
  form = $( 'pushit_lostPasswordForm' );
  pushit_sack.vars = new Array()
  pushit_sack.setVar("user_login", form.user_login.value);
  pushit_sack.setVar("way_sms",   ( form.retrive_way_sms.checked   ? 1 : 0 ) );
  pushit_sack.setVar("way_email", ( form.retrive_way_email.checked ? 1 : 0 ) );
  pushit_sack.setVar("action", 'forgotpass' );
  msg = '<img src="' + top.window.GB_ROOT_DIR + '../images/bigrotation2.gif" />';
  show_message( msg );

  callback = function(msg)
  {
    msg = msg || 'OK, your new password has been sent to you.';
    show_message( msg );
    return true;
  };  

  callback_error = function()
  {
    hide_message();
    return true;
  };
  
  pushit_request( callback, null, callback_error );
}

function GB_relocate(to, caption, height, width)
{
  width = width || 200;
  var doc = top.window.document;
  var frame = doc.getElementById('GB_frame');
  doc.getElementById('GB_Caption').innerHTML = caption;
  frame.src = to;
  change_height( height, 5 );
  return false;
}

function change_height( to, step )
{
  var doc = top.window.document;
  var frame = doc.getElementById('GB_frame');
  var win = top.window.GB_CURRENT.g_window;
  var size = parseInt( frame.style.height, 10 );
  var t = parseInt( win.style.top, 10 );
  var diff = to - size;
  if( Math.abs(diff) <= step )
  {
    return;
  }
  var inc = ( diff / step);
  frame.style.height = ( size + inc ) + 'px';
  win.style.top = ( t - inc/2 ) + 'px';
  setTimeout( 'change_height( ' + to + ',  ' + step + ' )', 10 );
}


function dump(obj, objName)
{
  objName = objName || 'obj';
  var result = "";
  for (var i in obj)
    result += objName + "." + i + " = " + obj[i] + "\n\n";
  alert(result);
}

function delHint( obj )
{
  if( 'text_clicked' != obj.className )
  {
    obj.title = obj.value;
    obj.value='';
    obj.className='text_clicked';
  }
}
function addHint( obj )
{
  if( 'text_clicked' == obj.className && '' == obj.value )
  {
    obj.className = 'text';
    obj.value = obj.title;
  }
}


var pushit_recommend_id = null;
// Add global onClick event
var tmpfunc = window.onclick
document.onclick = function(ev) {
	if (window.pushit_always_open) return;
	var el = (typeof event!=='undefined')? event.srcElement : ev.target
	if ( (pushit_recommend_id > 0) && (el.tagName!='A') ) {
		var inPushit = false
		var curNode = el
		while (!inPushit && curNode) {
			//alert(curNode +' '+ curNode.className)
			if ( curNode.className && (hasClass(curNode, 'pushit-link-tab-wrap') || hasClass(curNode, 'pushit-message')) ) {
				inPushit = true
			} else {
				curNode = curNode.parentNode
			}
		}
		if (!inPushit) {
			pushit_link_wnd(pushit_recommend_id, 'hide')
		}
	}
	if (typeof(tmpfunc) == 'function') tmpfunc()
}

function pushit_link_wnd(id, type) {
	if (type=='hide') {
		removeClass($('pushit-recommend-'+id), 'pushit-recommend-opened')
		removeClass($('pushit-recommend-'+id), 'pushit-tab-pushit')
		$('pushit-message-'+id).style.display = 'none'
		pushit_recommend_id = -1
	}
	if (type=='show') {
		if (pushit_recommend_id > 0) { pushit_link_wnd(pushit_recommend_id, 'hide') }
		addClass($('pushit-recommend-'+id), 'pushit-recommend-opened')
		addClass($('pushit-recommend-'+id), 'pushit-tab-pushit')
		pushit_recommend_id = id
	}
	removeClass($('pushit-recommend-'+id), 'pushit-tab-trackit')
	return false;
}

function pushit_trackit(id, action) {
	if (action=='show') {
		removeClass($('pushit-recommend-'+id), 'pushit-tab-pushit')
		addClass($('pushit-recommend-'+id), 'pushit-tab-trackit')
	}
	return false;
}

function pushit_input_hint_focus(inp) {
	if (inp.value == inp.title)
		inp.value = '';
	inp.className = '';
}
function pushit_input_hint_blur(inp) {
	if (inp.value=='' || inp.value==inp.title) {
		inp.value = inp.title
		inp.className = 'inact'
	}
}
function pushit_send(post_id) {
	if (ajax_busy()) return false
	
	var mobile = $('pushit_mobile_'+post_id);
	var mobile_cc = $('pushit_mobile_cc_'+post_id);
	var email  = $('pushit_email_'+post_id);
	
	pushit_sack.vars = Array();
	pushit_sack.setVar('action', 'email-sms');
	pushit_sack.setVar('post_id', post_id)
	
	// Send SMS
	if ((mobile.value!=mobile.title) && (mobile.value!='') )
		pushit_sack.setVar('msisdn', mobile_cc.value+mobile.value)
	// Send Email
	if ((email.value!=email.title) && (email.value!='') )
		pushit_sack.setVar('email', email.value)
	
	// Do send
	pushit_recommend_msg('load')
	pushit_sack.requestFile = top.window.pushit_base_uri + "/wp-content/plugins/pushit/backend.php";
	pushit_sack.method = "POST";
	pushit_sack.onError = function(){ alert(pushit_sack.response) }
	pushit_sack.onCompletion = pushit_send_complete
	pushit_sack.runAJAX()
	return false
}

function pushit_send_complete() {
	//alert(pushit_sack.response) 
	var d = pushit_sack.response.split('|');
	if (d[1]=='UNAUTHORIZED')
		return pushit_recommend_msg(pushit_messages[3])
	if (d[1]=='UNACTIVATED')
		return pushit_recommend_msg(pushit_messages[5])
	if (d[0]=='1')
		return pushit_recommend_msg( typeof(d[1])!='undefined' ? '<div style="text-align: center; margin-top: 15px; font-weight: bold;">'+d[1]+'</div>' : pushit_messages[4] )
	pushit_recommend_msg('<div style="text-align: center; margin-top: 40px; font-weight: bold;">'+d[1]+'</div>')
}

var pushit_messages = [
	// 0
	'<div style="text-align: center; margin-top: 5px; font-weight: bold;">'
	+'Enter your friend\'s cell phone number with a plus sign "+" before the country code.<br/><br/>'
	+'Example:  +46 709 123 456<br/>'
	+'(Always exclude initial zeroes)</div>',
	// 1
	'<div style="text-align: center; margin-top: 15px; font-weight: bold;">You can send an e-mail with a recommendation of this text to a friend.<br/><br/>Enter your friend\'s e-mail address</div>', 
	// 2
	'<div style="text-align: center; margin-top: 25px; font-weight: bold;">You can recommend this text on a social networking service.<br/><br/>Simply click any of the icons below in order to push this content to your friends</div>',
	// 3
	'<div style="text-align: center; margin-top: 25px; font-weight: bold;">You should be authorized to send recommendations.<br/><br/>'
	+'Please <a href="/wp-login.php">Login</a> or '
	+'<a href="/wp-login.php?action=register">Register</a>'
	+'</div>',
	// 4
	'<div style="text-align: center; margin-top: 35px; font-weight: bold;">Your recommendation successfully sent</div>',
	// 5
	'<div style="text-align: center; margin-top: 25px; font-weight: bold;">Your mobile number is not activated.<br/>You can activate the phone in your <a href="/wp-admin/profile.php#mobile_phone">profile</a> settings.</div>'
]
function pushit_recommend_msg(html) {
	if (pushit_recommend_id <=0 ) return;
	var el = $('pushit-message-'+pushit_recommend_id)
	if (html=='load') {
		addClass(el, 'rotating')
		el.style.display = 'block';
		el.innerHTML = '';
	} else {
		removeClass(el, 'rotating');
		el.innerHTML = html;
		el.style.display = 'block';
	}
}
function hasClass(ele,cls) { return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)')) }
function addClass(ele,cls) {if (!hasClass(ele,cls)) ele.className += " "+cls;}
function removeClass(ele,cls) {
	if (hasClass(ele,cls)) {
		var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
		ele.className=ele.className.replace(reg,' ');
	}
}
