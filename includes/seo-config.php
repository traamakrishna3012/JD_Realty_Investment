<?php
/**
 * SEO Configuration File for JD Realty & Investment
 * Defines all SEO-related settings and meta data
 */

// Include config to get SITE_URL
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}

// Define SEO settings
define('SEO_SITENAME', 'JD Realty & Investment');
define('SEO_DOMAIN', SITE_URL);
define('SEO_AUTHOR', 'JD Realty & Investment');

// Meta descriptions for each page type
$SEO_META = [
    'homepage' => [
        'title' => 'JD Realty & Investment - Real Estate Solutions | Buy Sell Properties Online',
        'description' => 'Find your perfect property on JD Realty & Investment. Browse residential, commercial, and plot properties. Trusted real estate platform for buying and selling properties online.',
        'keywords' => 'real estate, properties for sale, apartments, villas, commercial property, residential plot, property listing, buy property, sell property'
    ],
    'search' => [
        'title' => 'Search Properties - Find Real Estate Online | JD Realty & Investment',
        'description' => 'Search properties on JD Realty & Investment. Filter by location, type, and price to find your ideal property. Browse available residential, commercial, and plot listings.',
        'keywords' => 'property search, find properties, real estate listings, apartments for sale, commercial property, residential plot'
    ],
    'about' => [
        'title' => 'About JD Realty & Investment - Trusted Real Estate Company',
        'description' => 'Learn about JD Realty & Investment - A trusted real estate platform dedicated to helping you find your perfect property. Learn about our mission, vision, and core values.',
        'keywords' => 'about us, real estate company, JD Realty, property investment, trusted realtor'
    ],
    'property_details' => [
        'title' => '{title} - ₹{price} | JD Realty & Investment',
        'description' => '{description} - View detailed property information on JD Realty & Investment.',
        'keywords' => '{city}, {type}, property, real estate'
    ]
];

// Function to get meta tags for current page
function getSEOMeta($page_type, $data = [])
{
    global $SEO_META;

    if (!isset($SEO_META[$page_type])) {
        return null;
    }

    $meta = $SEO_META[$page_type];

    // Replace placeholders with actual data
    foreach ($data as $key => $value) {
        $meta['title'] = str_replace('{' . $key . '}', $value, $meta['title']);
        $meta['description'] = str_replace('{' . $key . '}', $value, $meta['description']);
        $meta['keywords'] = str_replace('{' . $key . '}', $value, $meta['keywords']);
    }

    return $meta;
}

// Function to generate structured data (JSON-LD)
function generateStructuredData($type, $data = [])
{
    $structured_data = [
        '@context' => 'https://schema.org',
        '@type' => $type
    ];

    if ($type === 'RealEstateAgent') {
        $structured_data = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateAgent',
            'name' => 'JD Realty & Investment',
            'url' => SITE_URL . '/',
            'description' => 'Trusted real estate platform for buying and selling properties',
            'areaServed' => 'India',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'Customer Service',
                'telephone' => '+91-XXXXXXXXXX'
            ]
        ];
    } elseif ($type === 'Property' && !empty($data)) {
        $structured_data = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateProperty',
            'name' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '',
                'addressLocality' => $data['city'] ?? '',
                'addressRegion' => 'India',
                'addressCountry' => 'IN'
            ],
            'price' => $data['price'] ?? 0,
            'priceCurrency' => 'INR',
            'numberOfRooms' => $data['bedrooms'] ?? 0,
            'numberOfBathroomsTotal' => $data['bathrooms'] ?? 0,
            'floorSize' => [
                '@type' => 'QuantitativeValue',
                'value' => $data['area_sqft'] ?? 0,
                'unitCode' => 'SQF'
            ]
        ];
    }

    return json_encode($structured_data);
}

// Function to generate breadcrumb navigation
function generateBreadcrumbs($breadcrumbs = [])
{
    $bread = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ]
    ];

    // Add home breadcrumb
    $bread[0]['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => SITE_URL . '/'
    ];

    $position = 2;
    foreach ($breadcrumbs as $name => $url) {
        $bread[0]['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $name,
            'item' => $url
        ];
        $position++;
    }

    return json_encode($bread);
}

// Function to output meta tags
function outputSEOMeta($page_type, $data = [])
{
    $meta = getSEOMeta($page_type, $data);

    if (!$meta)
        return '';

    $output = '';
    $output .= '<meta name="title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $output .= '<meta name="description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '<meta name="keywords" content="' . htmlspecialchars($meta['keywords']) . '">' . "\n";

    return $output;
}

// Function to output JSON-LD structured data
function outputStructuredData($type, $data = [])
{
    $json = generateStructuredData($type, $data);
    return '<script type="application/ld+json">' . $json . '</script>' . "\n";
}
?>