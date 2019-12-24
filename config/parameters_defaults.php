<?php

$defaultParameters = [
  'site_url'                        => '',
  'webroot'                         => '',
  'cache_path'                      => '%kernel.root_dir%/var/cache',
  'log_path'                        => '%kernel.root_dir%/var/logs',
  'image_path'                      => 'media/images',
  'tmp_path'                        => '%kernel.root_dir%/var/tmp',
  'theme'                           => 'Mauve',
  'theme_import_allowed_extensions' => [
    0  => 'json',
    1  => 'twig',
    2  => 'css',
    3  => 'js',
    4  => 'htm',
    5  => 'html',
    6  => 'txt',
    7  => 'jpg',
    8  => 'jpeg',
    9  => 'png',
    10 => 'gif',
  ],
  'db_driver'         => 'pdo_mysql',
  'db_host'           => '127.0.0.1',
  'db_port'           => 3306,
  'db_name'           => '',
  'db_user'           => '',
  'db_password'       => '',
  'db_table_prefix'   => '',
  'db_server_version' => '5.5',
  'locale'            => 'en_US',
  'secret_key'        => '',
  'dev_hosts'         => [
  ],
  'trusted_hosts' => [
  ],
  'trusted_proxies' => [
  ],
  'rememberme_key'       => '8ca8139125aefe02184ba284c3ad7b3e095abd53',
  'rememberme_lifetime'  => 31536000,
  'rememberme_path'      => '/',
  'rememberme_domain'    => '',
  'default_pagelimit'    => 30,
  'default_timezone'     => 'UTC',
  'date_format_full'     => 'F j, Y g:i a T',
  'date_format_short'    => 'D, M d',
  'date_format_dateonly' => 'F j, Y',
  'date_format_timeonly' => 'g:i a',
  'ip_lookup_service'    => 'maxmind_download',
  'ip_lookup_auth'       => '',
  'ip_lookup_config'     => [
  ],
  'ip_lookup_create_organization' => false,
  'transifex_username'            => '',
  'transifex_password'            => '',
  'update_stability'              => 'stable',
  'cookie_path'                   => '/',
  'cookie_domain'                 => '',
  'cookie_secure'                 => null,
  'cookie_httponly'               => false,
  'do_not_track_ips'              => [
  ],
  'do_not_track_bots' => [
    0  => 'MSNBOT',
    1  => 'msnbot-media',
    2  => 'bingbot',
    3  => 'Googlebot',
    4  => 'Google Web Preview',
    5  => 'Mediapartners-Google',
    6  => 'Baiduspider',
    7  => 'Ezooms',
    8  => 'YahooSeeker',
    9  => 'Slurp',
    10 => 'AltaVista',
    11 => 'AVSearch',
    12 => 'Mercator',
    13 => 'Scooter',
    14 => 'InfoSeek',
    15 => 'Ultraseek',
    16 => 'Lycos',
    17 => 'Wget',
    18 => 'YandexBot',
    19 => 'Java/1.4.1_04',
    20 => 'SiteBot',
    21 => 'Exabot',
    22 => 'AhrefsBot',
    23 => 'MJ12bot',
    24 => 'NetSeer crawler',
    25 => 'TurnitinBot',
    26 => 'magpie-crawler',
    27 => 'Nutch Crawler',
    28 => 'CMS Crawler',
    29 => 'rogerbot',
    30 => 'Domnutch',
    31 => 'ssearch_bot',
    32 => 'XoviBot',
    33 => 'digincore',
    34 => 'fr-crawler',
    35 => 'SeznamBot',
    36 => 'Seznam screenshot-generator',
    37 => 'Facebot',
    38 => 'facebookexternalhit',
  ],
  'do_not_track_internal_ips' => [
  ],
  'track_private_ip_ranges'   => false,
  'link_shortener_url'        => null,
  'cached_data_timeout'       => 10,
  'batch_sleep_time'          => 1,
  'batch_campaign_sleep_time' => false,
  'cors_restrict_domains'     => true,
  'cors_valid_domains'        => [
  ],
  'rss_notification_url'              => 'https://mautic.com/?feed=rss2&tag=notification',
  'translations_list_url'             => 'https://language-packs.mautic.com/manifest.json',
  'translations_fetch_url'            => 'https://language-packs.mautic.com/',
  'system_update_url'                 => 'https://updates.mautic.org/index.php?option=com_mauticdownload&task=checkUpdates',
  'max_entity_lock_time'              => 0,
  'default_daterange_filter'          => '-1 month',
  'api_enabled'                       => false,
  'api_enable_basic_auth'             => false,
  'api_oauth2_access_token_lifetime'  => 60,
  'api_oauth2_refresh_token_lifetime' => 14,
  'api_batch_max_limit'               => 200,
  'api_rate_limiter_limit'            => 0,
  'api_rate_limiter_cache'            => [
    'type' => 'file_system',
  ],
  'upload_dir'         => '%kernel.root_dir%/../media/files',
  'max_size'           => '6',
  'allowed_extensions' => [
    0  => 'csv',
    1  => 'doc',
    2  => 'docx',
    3  => 'epub',
    4  => 'gif',
    5  => 'jpg',
    6  => 'jpeg',
    7  => 'mpg',
    8  => 'mpeg',
    9  => 'mp3',
    10 => 'odt',
    11 => 'odp',
    12 => 'ods',
    13 => 'pdf',
    14 => 'png',
    15 => 'ppt',
    16 => 'pptx',
    17 => 'tif',
    18 => 'tiff',
    19 => 'txt',
    20 => 'xls',
    21 => 'xlsx',
    22 => 'wav',
  ],
  'campaign_time_wait_on_event_false' => 'PT1H',
  'dashboard_import_dir'              => '%kernel.root_dir%/../media/dashboards',
  'dashboard_import_user_dir'         => null,
  'mailer_api_key'                    => null,
  'mailer_from_name'                  => 'Mautic',
  'mailer_from_email'                 => 'email@yoursite.com',
  'mailer_return_path'                => null,
  'mailer_transport'                  => 'smtp',
  'mailer_append_tracking_pixel'      => true,
  'mailer_convert_embed_images'       => false,
  'mailer_host'                       => '',
  'mailer_port'                       => null,
  'mailer_user'                       => null,
  'mailer_password'                   => null,
  'mailer_encryption'                 => null,
  'mailer_auth_mode'                  => null,
  'mailer_amazon_region'              => 'email-smtp.us-east-1.amazonaws.com',
  'mailer_custom_headers'             => [
  ],
  'mailer_spool_type'            => 'memory',
  'mailer_spool_path'            => '%kernel.root_dir%/var/spool',
  'mailer_spool_msg_limit'       => null,
  'mailer_spool_time_limit'      => null,
  'mailer_spool_recover_timeout' => 900,
  'mailer_spool_clear_timeout'   => 1800,
  'unsubscribe_text'             => null,
  'webview_text'                 => null,
  'unsubscribe_message'          => null,
  'resubscribe_message'          => null,
  'monitored_email'              => [
    'general' => [
      'address'         => null,
      'host'            => null,
      'port'            => '993',
      'encryption'      => '/ssl',
      'user'            => null,
      'password'        => null,
      'use_attachments' => false,
    ],
    'EmailBundle_bounces' => [
      'address'           => null,
      'host'              => null,
      'port'              => '993',
      'encryption'        => '/ssl',
      'user'              => null,
      'password'          => null,
      'override_settings' => 0,
      'folder'            => null,
    ],
    'EmailBundle_unsubscribes' => [
      'address'           => null,
      'host'              => null,
      'port'              => '993',
      'encryption'        => '/ssl',
      'user'              => null,
      'password'          => null,
      'override_settings' => 0,
      'folder'            => null,
    ],
    'EmailBundle_replies' => [
      'address'           => null,
      'host'              => null,
      'port'              => '993',
      'encryption'        => '/ssl',
      'user'              => null,
      'password'          => null,
      'override_settings' => 0,
      'folder'            => null,
    ],
  ],
  'mailer_is_owner'                     => false,
  'default_signature_text'              => null,
  'email_frequency_number'              => null,
  'email_frequency_time'                => null,
  'show_contact_preferences'            => false,
  'show_contact_frequency'              => false,
  'show_contact_pause_dates'            => false,
  'show_contact_preferred_channels'     => false,
  'show_contact_categories'             => false,
  'show_contact_segments'               => false,
  'mailer_mailjet_sandbox'              => false,
  'mailer_mailjet_sandbox_default_mail' => null,
  'disable_trackable_urls'              => false,
  'form_upload_dir'                     => '%kernel.root_dir%/../media/files/form',
  'blacklisted_extensions'              => [
    0 => 'php',
    1 => 'sh',
  ],
  'parallel_import_limit'               => 1,
  'background_import_if_more_rows_than' => 0,
  'notification_enabled'                => false,
  'notification_landing_page_enabled'   => true,
  'notification_tracking_page_enabled'  => false,
  'notification_app_id'                 => null,
  'notification_rest_api_key'           => null,
  'notification_safari_web_id'          => null,
  'gcm_sender_id'                       => '482941778795',
  'notification_subdomain_name'         => null,
  'welcomenotification_enabled'         => true,
  'cat_in_page_url'                     => false,
  'google_analytics'                    => false,
  'track_contact_by_ip'                 => false,
  'track_by_fingerprint'                => false,
  'track_by_tracking_url'               => false,
  'redirect_list_types'                 => [
    301 => 'mautic.page.form.redirecttype.permanent',
    302 => 'mautic.page.form.redirecttype.temporary',
  ],
  'google_analytics_id'                   => null,
  'google_analytics_trackingpage_enabled' => false,
  'google_analytics_landingpage_enabled'  => false,
  'google_analytics_anonymize_ip'         => false,
  'facebook_pixel_id'                     => null,
  'facebook_pixel_trackingpage_enabled'   => false,
  'facebook_pixel_landingpage_enabled'    => false,
  'queue_protocol'                        => '',
  'rabbitmq_host'                         => 'localhost',
  'rabbitmq_port'                         => '5672',
  'rabbitmq_vhost'                        => '/',
  'rabbitmq_user'                         => 'guest',
  'rabbitmq_password'                     => 'guest',
  'beanstalkd_host'                       => 'localhost',
  'beanstalkd_port'                       => '11300',
  'beanstalkd_timeout'                    => '60',
  'report_temp_dir'                       => '%kernel.root_dir%/../media/files/temp',
  'report_export_batch_size'              => 1000,
  'report_export_max_filesize_in_bytes'   => 5000000,
  'csv_always_enclose'                    => false,
  'sms_enabled'                           => false,
  'sms_username'                          => null,
  'sms_password'                          => null,
  'sms_sending_phone_number'              => null,
  'sms_frequency_number'                  => null,
  'sms_frequency_time'                    => null,
  'sms_transport'                         => 'mautic.sms.transport.twilio',
  'saml_idp_metadata'                     => '',
  'saml_idp_entity_id'                    => '',
  'saml_idp_own_certificate'              => '',
  'saml_idp_own_private_key'              => '',
  'saml_idp_own_password'                 => '',
  'saml_idp_email_attribute'              => '',
  'saml_idp_username_attribute'           => '',
  'saml_idp_firstname_attribute'          => '',
  'saml_idp_lastname_attribute'           => '',
  'saml_idp_default_role'                 => '',
  'webhook_limit'                         => 10,
  'webhook_log_max'                       => 1000,
  'webhook_disable_limit'                 => 100,
  'webhook_timeout'                       => 15,
  'queue_mode'                            => 'immediate_process',
  'events_orderby_dir'                    => 'ASC',
  'website_snapshot_url'                  => 'https://mautic.net/api/snapshot',
  'website_snapshot_key'                  => '',
  'twitter_handle_field'                  => 'twitter',
  'supported_languages'                   => [
    'en_US' => 'English - United States',
  ],
];
