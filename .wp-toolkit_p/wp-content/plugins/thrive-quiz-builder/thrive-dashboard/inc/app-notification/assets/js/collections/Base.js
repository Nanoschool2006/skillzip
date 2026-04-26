module.exports = Backbone.Collection.extend( {
	/**
	 * helper function to get the last item of a collection
	 *
	 * @return Backbone.Model
	 */
	last: function () {
		return this.at( this.size() - 1 );
	}
} );
