const PRIVATE_JQUERY_EVENT = '_jquery_ready';
// return user data in format { String firstName;
//    String lastName;
//    String email;
//    String city;
//    String country;
//    String preferredLanguage;
//    String credit;
//    String gender;}
const PRIVATE_USER_DATA_EVENT = '_user_data';
// return user's event data in format
// {[
//      String name;
//      String date;
//     TimeOfDay? startTime;
//      String ticketLink;
//      String ticketInfoLink;
//      int numberOfTickets;   
// ]
// }
const PRIVATE_USER_EVENT_DATA_EVENT = '_user_event_data';
const PRIVATE_LOADING_DATA_EVENT = '_data_loading';

//this will be ignored by the static-manager's jquery.ready replacement 
// called only once on init
(function ($) {
    $(document).ready(function () {
        console.log('jquery.ready');
        $(document).trigger(PRIVATE_JQUERY_EVENT, [window.jQuery]);
    });


    //test for the app event listener
    $(document).on(PRIVATE_USER_DATA_EVENT, function (e, data) {
        console.log('user data ');
        console.log(data);
        // alert(JSON.stringify(data));
    });
    $(document).on(PRIVATE_USER_EVENT_DATA_EVENT, function (e, data) {
        console.log('user data events');
        console.log(data);
        // alert(JSON.stringify(e));
        // alert(JSON.stringify(data));
    });
    $(document).on(PRIVATE_LOADING_DATA_EVENT, function (e, data) {
        console.log('loading data');
        console.log(data);
        // alert(JSON.stringify(e));
        // alert(JSON.stringify(data));
    });
})(jQuery)