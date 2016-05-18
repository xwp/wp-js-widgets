/* eslint-env node */

var React = require( 'react' );

var Form = React.createClass({

	propTypes: {
		labelTitle: React.PropTypes.string,
		placeholderTitle: React.PropTypes.string,
		labelNumber: React.PropTypes.string,
		labelShowDate: React.PropTypes.string,
		minimumNumber: React.PropTypes.number,
		store: React.PropTypes.object
	},

	/**
	 * Default props.
	 *
	 * @returns {object} Default.
	 */
	getDefaultProps: function() {
		return {
			labelTitle: '',
			placeholderTitle: '',
			labelNumber: '',
			labelShowDate: false,
			minimumNumber: 1
		}
	},

	/**
	 * Handle field change.
	 *
	 * @param {object} e Event.
	 * @returns {void}
	 */
	onChange: function( e ) {
		var value, props = {};
		if ( 'checkbox' === e.target.type ) {
			value = e.target.checked;
		} else {
			value = e.target.value;
		}
		props[ e.target.name ] = value;
		this.props.store.dispatch( {
			'type': 'UPDATE',
			'props': props
		} );
	},

	/**
	 * Render.
	 *
	 * @todo Break this up into a container component and three nested components: TitleInput, NumberInput, ShowDateInput. Or rather just TextInput and CheckboxInput.
	 *
	 * @returns {XML} Element.
	 */
	render: function() {
		var state = this.props.store.getState();
		return (
			<fieldset>
				<p>
					<label>
						{this.props.labelTitle}
						<input class="widefat" type="text" name="title" value={state.title} placeholder={this.props.placeholderTitle} onChange={this.onChange} />
					</label>
				</p>
				<p>
					<label>
						{this.props.labelNumber}
						<input class="widefat" type="number" value={state.number} min={this.props.minimumNumber} name="number" onChange={this.onChange} />
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="show_date" checked={state.show_date} onChange={this.onChange} />
						{this.props.labelShowDate}
					</label>
				</p>
			</fieldset>
		);
	}
});

module.exports = Form;
