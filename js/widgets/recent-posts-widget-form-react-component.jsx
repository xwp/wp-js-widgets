/* eslint-env node */

var React = require( 'react' );

var Form = React.createClass({

	propTypes: {
		labelTitle: React.PropTypes.string,
		placeholderTitle: React.PropTypes.string,
		labelNumber: React.PropTypes.string,
		labelShowDate: React.PropTypes.string,
		minimumNumber: React.PropTypes.number,
		changeCallback: React.PropTypes.func // @todo revisit with Flex/Redux
	},

	/**
	 * Default props.
	 *
	 * @returns {{labelTitle: string, placeholderTitle: string, labelNumber: string, labelShowDate: boolean, minimumNumber: number}}
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
	 * Handle title change.
	 *
	 * @todo revisit with Flex/Redux
	 *
	 * @param {object} e
	 */
	handleTitleChange: function( e ) {
		this.props.changeCallback( { title: e.target.value } );
	},

	/**
	 * Handle number change.
	 *
	 * @todo revisit with Flex/Redux
	 *
	 * @param {object} e
	 */
	handleNumberChange: function( e ) {
		this.props.changeCallback( { number: e.target.value } );
	},

	/**
	 * Handle show date change.
	 *
	 * @todo revisit with Flex/Redux
	 *
	 * @param {object} e
	 */
	handleShowDateChange: function( e ) {
		this.props.changeCallback( { show_date: e.target.checked } );
	},

	/**
	 * Get initial state.
	 *
	 * @returns {{title: string, number: number, show_date: boolean}}
	 */
	getInitialState: function() {
		return {
			title: '',
			number: 5,
			show_date: false
		};
	},

	/**
	 * Render
	 *
	 * @todo Break this up into three nested components: TitleInput, NumberInput, ShowDateInput. Or rather just TextInput and CheckboxInput.
	 *
	 * @returns {XML}
	 */
	render: function() {
		return (
			<fieldset>
				<p>
					<label>
						{this.props.labelTitle}
						<input class="widefat" type="text" name="title" value={this.state.title} placeholder={this.props.placeholderTitle} onChange={this.handleTitleChange} />
					</label>
				</p>
				<p>
					<label>
						{this.props.labelNumber}
						<input class="widefat" type="number" value={this.state.number} min={this.props.minimumNumber} name="number" onChange={this.handleNumberChange} />
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="show_date" checked={this.state.show_date} onChange={this.handleShowDateChange} />
						{this.props.labelShowDate}
					</label>
				</p>
			</fieldset>
		);
	}
});

module.exports = Form;
