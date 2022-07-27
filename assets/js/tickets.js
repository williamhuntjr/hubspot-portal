$( document ).ready(function() {
//$( ".ticket-conversation .chat-body" ).empty();


  $( "#ticket-container .ticket-list li" ).click(function() {
    console.log(tickets[$(this).attr('ticket-num') - 1]);
  });

});
