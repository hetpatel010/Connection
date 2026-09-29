<?php

return [

    'vendor' => [
        'name' => 'Sveo Technologies',
        'brand' => 'SVEO',
        'legal_name' => 'Shree Verai Enterprise (OPC) Private Limited',
        'website' => 'https://sveo.in',
        'website_label' => 'sveo.in',
        'contact_url' => 'https://sveo.in/contact',
        'email' => env('BRANDING_VENDOR_EMAIL', 'hello@sveo.in'),
        'phone' => env('BRANDING_VENDOR_PHONE'),
        'about_url' => 'https://sveo.in/about',
        'logo_url' => 'https://sveo.in/assets/logo-D7EKQNMr.png',
        'tagline' => 'Build skills. Shape ideas. Move forward with technology.',
        'location' => 'Patan, Gujarat, India',
        'address' => '3rd Floor, Block B, Shops T26–T34, Mashruwala Complex, Patan, Gujarat 384265',
        'description' => 'SVEO Technologies delivers thoughtful software — web applications, APIs, full-stack products, cloud, and technical consulting from Patan, Gujarat.',
        'services' => [
            'Laravel Development',
            'Full Stack Development',
            'React & Angular Development',
            'Cloud & DevOps',
            'AI & GenAI Development',
            'IT Consulting & Support',
        ],
    ],

    'product' => [
        'name' => env('APP_PRODUCT_NAME', env('APP_NAME', 'Billing')),
        'short_name' => env('APP_PRODUCT_SHORT_NAME', 'Billing'),
        'version' => env('APP_VERSION'),
        'tagline' => 'Billing, accounting, inventory, and GST reporting for your business.',
        'theme_color' => '#4338ca',
        'favicon' => '/favicon.svg',
        'icon' => '/favicon.svg',
    ],

    'credit_line' => 'Designed & developed by :vendor',

];
