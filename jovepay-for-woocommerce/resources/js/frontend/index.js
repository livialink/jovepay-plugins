
import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'jovepay_data', {} );

const defaultLabel = __(
	'JOVEpay',
	'jovepay-for-woocommerce'
);

const label = decodeEntities( settings.title ) || defaultLabel;
const logoUrl = settings.icon || '';
const coinIcons = Array.isArray( settings.icons ) ? settings.icons : [];

/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};

/**
 * Overlapping coin stack shown after the payment method title.
 */
const CoinStack = () => {
	if ( ! coinIcons.length ) {
		return null;
	}

	return (
		<span className="jpwc-payment-icons" aria-hidden="true">
			<span className="jpwc-payment-icons__stack">
				{ coinIcons.map( ( coin, index ) => (
					<img
						key={ coin.id || coin.src || index }
						className="jpwc-payment-icons__coin"
						src={ coin.src }
						alt=""
						style={ { zIndex: coinIcons.length - index } }
					/>
				) ) }
			</span>
		</span>
	);
};

/**
 * Label component — logo prefixes the method name; coins follow.
 */
const Label = () => {
	return (
		<span className="jpwc-payment-method-label">
			{ logoUrl ? (
				<img
					className="jpwc-payment-icons__logo"
					src={ logoUrl }
					alt=""
				/>
			) : null }
			<span className="jpwc-payment-method-label__text">{ label }</span>
			<CoinStack />
		</span>
	);
};

/**
 * JOVEpay method config object.
 */
const gatewayConfig = {
	name: 'jovepay',
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( gatewayConfig );
