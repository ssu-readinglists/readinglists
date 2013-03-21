M.mod_refworks = {

	init: function(Y) {
		this.Y = Y;
		// Create a custom Acfilter class that extends AutoCompleteBase.
		var Acfilter = Y.Base.create('AcFilter', Y.Base, [Y.AutoCompleteBase], {
			initializer: function () {
				this._bindUIACBase();
				this._syncUIACBase();
			}
		});
		
		// Create and configure an instance of the Acfilter class.
		filter = new Acfilter({
	    	inputNode: '#ac-input',
	    	minQueryLength: 0,
	    	queryDelay: 0,

	    	// Run an immediately-invoked function that returns an array of results to
	    	// be used for each query, based on the shared/collaborative accounts on the page.
	
	    	source: (function () {
	      		var results = [];

	      		// Build an array of results containing each account in the list.
	      		Y.all('.account').each(function (node) {
	        		results.push({
	          			node: node,
	          			name: node.get('text')
	        		});
	      		});
	      		return results;
	    	}()), // <-- Note the parens. This invokes the function immediately.
	        //     Remove these to invoke the function on every query instead.

			// Specify that the "name" property of each result object contains the text
			// to filter on.
			resultTextLocator: 'name',
	
			// Use a result filter to filter the account results based on their names.
			resultFilters: 'phraseMatch'
		});
		
		filter.on('results', function (e) {
			
			// First hide all the photos.
			Y.all('.account').addClass('hidden');

			// Then unhide the ones that are in the current result list.
			Y.Array.each(e.results, function (result) {
				result.raw.node.removeClass('hidden');
			});
		});
	}
}
