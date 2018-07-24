<?php
// https://github.com/tsugiproject/trophy
require_once "../config.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

// Handle all forms of launch
$LTI = LTIX::requireData();

// Render view
$OUTPUT->header();
?>
<style>
@media only screen and (max-width: 600px) {
    #present_top {
        display:none;
    }
}
</style>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();

$OUTPUT->welcomeUserCourse();

?>
<p>
<form action="message.php" id="messageForm">
  <input type="text" name="message" style="width:80%;" placeholder="Message...">
  <span id="spinner" class="fa fa-spinner fa-pulse" style="display:none;"></span>
</form>
</p>

<div id="present_top" style="float: right; width: 20%;">
<div id="present_top_content" style="width: 100%;"></div>
</div>

<div id="chatcontent">
    <span class="fa fa-spinner fa-pulse"></span>
</div>

<?php
$OUTPUT->footerStart();
?>

<script>

var _SIMPLECHAT_LAST_MICRO_TIME = 0;
var _SIMPLECHAT_TIMER = false;

// https://stackoverflow.com/questions/18749591/encode-html-entities-in-javascript
if (typeof htmlentities != 'function')
{
function htmlentities(raw) {
    var span = document.createElement("span");
    span.textContent = raw;
    return span.innerHTML;
}
}

function handleMessages(data) {
      if ( _SIMPLECHAT_LAST_MICRO_TIME == 0 ) $('#chatcontent').empty();
      if ( data.messages ) {
          for (var i = 0; i < data.messages.length; i++) {
            var arow = data.messages[i];
            if ( arow.micro_time && arow.micro_time > _SIMPLECHAT_LAST_MICRO_TIME ) _SIMPLECHAT_LAST_MICRO_TIME = arow.micro_time;
            var newtext = '<p>';
            if ( arow.image && arow.image.length > 1) {
                newtext += '<img src="'+encodeURI(arow.image)+'" width="20px" style="float:left; padding:3px;"/> ';
            }

            newtext += '\n' + htmlentities(arow.displayname) +
                ' ' + htmlentities(arow.relative) +
                '<br/>&nbsp;&nbsp;'+htmlentities(arow.message)+'<br clear="all"></p>\n';
            $('#chatcontent').prepend(newtext);
          }
      }
      if ( data.present ) {
           $('#present_top_content').empty();
          for (var i = 0; i < data.present.length; i++) {
            var arow = data.present[i];
            var newtext = '<p>';
            if ( arow.image && arow.image.length > 1) {
                newtext += '<img src="'+encodeURI(arow.image)+'" width="20px" style="padding:3px;"/> ';
            }

            newtext += '\n' + htmlentities(arow.displayname) + '</p>\n';
            $('#present_top_content').append(newtext);
          }
      }
      _SIMPLECHAT_TIMER = setTimeout('doPoll()', 8000);
}

// https://api.jquery.com/jquery.post/
// Attach a submit handler to the form
$( "#messageForm" ).submit(function( event ) {

  $("#spinner").show();

  // Stop form from submitting normally
  event.preventDefault();

  // Get some values from elements on the page:
  var $form = $( this ),
    term = $form.find( "input[name='message']" ).val(),
    session = $form.find( "input[name='PHPSESSID']" ).val(),
    url = $form.attr( "action" );

    $form.find( "input[name='message']" ).val('');

  if ( term.len < 1 ) {
    $("#spinner").hide();
    return;
  }

  if ( _SIMPLECHAT_TIMER ) clearTimeout(_SIMPLECHAT_TIMER);
  _SIMPLECHAT_TIMER = false;

  // Send the data using post
  var posting = $.post( url,
    { message: term, PHPSESSID: session, since: _SIMPLECHAT_LAST_MICRO_TIME } );

  // Put the results in a div
  posting.done(function( data ) {
    doPoll();
    $("#spinner").hide();
  });

});

function doPoll() {
  var messageurl = addSession('message.php?since='+_SIMPLECHAT_LAST_MICRO_TIME);
  $.getJSON(messageurl, function(data){
    handleMessages(data);
  });
}

// Make sure JSON requests are not cached
$(document).ready(function() {
  $.ajaxSetup({ cache: false });
  doPoll();
});
</script>


<?php
$OUTPUT->footerEnd();

