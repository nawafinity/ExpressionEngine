<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Fluid block parser class
 */
class EE_Channel_fluid_field_parser implements EE_Channel_parser_component {

	/**
	 * Check if Fluid Block is enabled
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->ffields) OR in_array('fluid_fields', $disabled);
	}

	// --------------------------------------------------------------------

	/**
	 * Gather the data needed to process all Fluid Block fields
	 *
	 * The returned object will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return NULL
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$fluid_field_field_names = array();

		// Run the preprocessor for each site
		foreach ($pre->site_ids() as $site_id)
		{
			$ffields = $pre->channel()->ffields;

			// Skip a site if it has no Fluid Block fields
			if ( ! isset($ffields[$site_id]) OR empty($ffields[$site_id]))
			{
				continue;
			}

			$fluid_field_field_names = array_merge($fluid_field_field_names, array_values(array_flip($ffields[$site_id])));

			ee()->load->library('fluid_field_parser');
			ee()->fluid_field_parser->pre_process($tagdata, $pre, $ffields[$site_id]);
		}

		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the Fliud Block fields in one fell swoop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $fluid_field_parser)
	{
		return $tagdata;
	}
}
