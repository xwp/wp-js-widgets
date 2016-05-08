/* eslint-env node */

var React = require( 'react' );
var ReactDOM = require( 'react-dom' );

var Form = React.createClass({

	// propTypes: {
	// 	email:      React.PropTypes.string,
	// 	seats:      React.PropTypes.number,
	// 	settings:   React.PropTypes.object,
	// 	callback:   React.PropTypes.func,
	// 	isClosed:   React.PropTypes.bool,
	// 	any:        React.PropTypes.any,
	// }

	/**
	 * @todo Let this be passed in from form_config.
	 *
	 * @returns {{label_title: string, placeholder_title: string, label_number: string, label_show_date: boolean, minimum_number: number}}
	 */
	getDefaultProps: function() {
		return {
			label_title: '',
			placeholder_title: '',
			label_number: '',
			label_show_date: false,
			minimum_number: 1
		}
	},

	/**
	 * @todo Let this be passed in from default_instance?
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

	render: function() {
		return (
			<fieldset>
				<p>
					<label>
						{ this.props.label_title }
						<input class="widefat" type="text" name="title" placeholder="{ this.props.placeholder_title }" />
					</label>
				</p>
				<p>
					<label>
						{ this.props.label_number }
						<input class="widefat" type="number" min="{ this.props.minimum_number }" name="number" />
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="show_date" />
						{ this.props.label_show_date }
					</label>
				</p>
			</fieldset>
		);
	}
});

function renderForm( container, formConfig ) {
	ReactDOM.render(
		<Form {...formConfig} />,
		container
	);
}

// @todo global??

module.exports = renderForm;
