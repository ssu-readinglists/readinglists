M.filter_refshares = {

    init: function(Y, refshare, style, div, mroot, load, error) {
        this.Y = Y;
        
		// capture the node that we'll display the messages in
		var target = Y.one("#"+div);
		var err = error + '<a href="'+ decodeURIComponent(refshare) + '">' + decodeURIComponent(refshare) + '</a>.';
		target.setHTML(load);
		// Create the io callback/configuration
		var callback = {
			//Giving it 3 minutes
			timeout : 180000,

			on : {
				success : function (x,o) {

					var messages = [],
					html = '', i, l;

					// Process the JSON data returned from the server
					try {
						messages = Y.JSON.parse(o.responseText);
					}
					catch (e) {
						// Need to amend this so it inserts a useful message in the browser div (as well)
						target.setHTML(err)
						Y.log("JSON Parse failed!");
						return;
					}

					Y.log("PARSED DATA: " + Y.Lang.dump(messages));

					// The returned data was parsed into an array of objects.
					// Add a P element for each received message
					html = messages;

					// Use the Node API to apply the new innerHTML to the target
					target.setHTML(html);
				},

				failure : function (x,o) {
					// Need to amend this so it inserts a useful message in the browser div (as well)
					target.setHTML(err)
					Y.log("Async call failed!");
				}

			}
		};
		
		Y.io(mroot + "/filter/refshares/refshare_json.php?refshare=" + refshare + "&style=" + style, callback);
	}
}
