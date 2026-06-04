/**
 * Scripts to modify the core blocks behabior
 */

wp.domReady(
	() => {
		// Remove styles for Image Block.
		//wp.blocks.unregisterBlockStyle( 'core/image', 'rounded' );
	}
);

import { __ } from '@wordpress/i18n';
import { registerBlockExtension } from '@10up/block-components';
import { PanelBody, TextareaControl, RangeControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

const blockName = 'core/button';

const additionalAttributes = {
	accessibleText: {
		type: 'string',
		default: '',
	}
};

/**
 * BlockEdit
 *
 * a react component that will get mounted in the Editor when the block is
 * selected. It is recommended to use Slots like `BlockControls` or `InspectorControls`
 * in here to put settings into the blocks toolbar or sidebar.
 *
 * @param {object} props block props
 * @returns {HTMLElement}
 */
const BlockEdit = (props) => {
	const {
		attributes: { accessibleText },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<PanelBody title={__('Additional Information')}>
				<TextareaControl
					value={accessibleText}
					label={__('Accessible Text')}
					help={__(
						'This replaces what screenreader will read as the link text.',
						'tenup-theme',
					)}
					onChange={(value) => {
						setAttributes({
							accessibleText: value,
						});
					}}
				/>
			</PanelBody>
		</InspectorControls>
	);
};

registerBlockExtension(blockName, {
	extensionName: 'button-updates',
	attributes: additionalAttributes,
	classNameGenerator: () => null,
	inlineStyleGenerator: () => null,
	Edit: BlockEdit,
});