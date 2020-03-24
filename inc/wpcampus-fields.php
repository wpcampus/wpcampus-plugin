<?php

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	return;
}

acf_add_local_field_group(
	[
		'key'                   => 'group_5e797e462b1b3',
		'title'                 => 'Your WPCampus member information',
		'fields'                => [
			[
				'key'               => 'field_5e797e6ad5df0',
				'label'             => 'Company',
				'name'              => 'company',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'default_value'     => '',
				'placeholder'       => 'Where do you work?',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			],
			[
				'key'               => 'field_5e797e88d5df1',
				'label'             => 'Company position',
				'name'              => 'company_position',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'default_value'     => '',
				'placeholder'       => 'What is your job title?',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			],
			[
				'key'               => 'field_5e797f989e640',
				'label'             => 'WPCampus Slack username',
				'name'              => 'slack_username',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'default_value'     => '',
				'placeholder'       => 'What is your Slack username?',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			],
		],
		'location'              => [
			[
				[
					'param'    => 'user_form',
					'operator' => '==',
					'value'    => 'all',
				],
			],
		],
		'menu_order'            => - 100,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
	]
);
