$(function() {
    var $text = $('#text');

    function log(message) {
        console.log(message);
        $text.append("\n" + message);
    }

    var ws = new WebSocket("ws://10.58.2.173:8089/chat");

    ws.onopen = function () {
        log("Opening a connection...");
        window.identified = false;
    };
    ws.onclose = function (evt) {
        //setTimeout(function(){ws = new WebSocket("ws://10.58.2.173:8089/chat")}, 1000);
        log("I'm sorry. Bye!");
    };
    ws.onmessage = function (evt) {
        log(' => ' + evt.data);
    };
    ws.onerror = function (evt) {
        log("ERR: " + evt.data);
        console.error(evt);
    };

    var send = function (message, callback) {
        waitForConnection(function () {
            ws.send(message);
            log(' <= ' + message);
            if (typeof callback !== 'undefined') {
                callback();
            }
        }, 1000);
    };

    var waitForConnection = function (callback, interval) {
        if (ws.readyState === 1) {
            callback();
        } else {
            // optional: implement backoff for interval here
            setTimeout(function () {
                waitForConnection(callback, interval);
            }, interval);
        }
    };

    var close = function() {
        ws.close();
    };

    window.send = send;
    window.close = close;
});
