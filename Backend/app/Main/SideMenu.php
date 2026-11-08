<?php

namespace App\Main;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SideMenu
{
    /**
     * List of side menu items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public static function menu()
    {
        $professionTitle = strtolower(getProfessionTitle() ?: 'partner');

        return [
            'dashboard' => [
                'icon' => 'home',
                'route_name' => 'dashboard',
                'params' => '',
                'title' => 'Dashboard',
            ],
            'growth-os' => [
                'icon' => 'zap',
                'title' => 'Growth & AI OS',
                'sub_menu' => [
                    'growth-dashboard' => [
                        'icon' => 'activity',
                        'route_name' => 'growth-os.dashboard',
                        'params' => '',
                        'title' => '🎯 Growth & Goals',
                    ],
                    'growth-keywords' => [
                        'icon' => 'key',
                        'route_name' => 'growth-os.keywords',
                        'params' => '',
                        'title' => '🔑 Keywords & Silos',
                    ],
                    'growth-content' => [
                        'icon' => 'file-text',
                        'route_name' => 'growth-os.content',
                        'params' => '',
                        'title' => '📝 Content Pipeline',
                    ],
                    'growth-seo' => [
                        'icon' => 'shield-check',
                        'route_name' => 'growth-os.seo',
                        'params' => '',
                        'title' => '🔍 SEO Auditor & Fixer',
                    ],
                    'growth-social' => [
                        'icon' => 'share-2',
                        'route_name' => 'growth-os.social',
                        'params' => '',
                        'title' => '📅 Social & Festivals',
                    ],
                    'growth-marketing' => [
                        'icon' => 'send',
                        'route_name' => 'growth-os.marketing',
                        'params' => '',
                        'title' => '📣 Marketing & B2B',
                    ],
                    'growth-autopilot' => [
                        'icon' => 'zap',
                        'route_name' => 'growth-os.autopilot',
                        'params' => '',
                        'title' => '⚡ Autonomous Autopilot',
                    ],
                    'growth-ai' => [
                        'icon' => 'cpu',
                        'route_name' => 'growth-os.ai',
                        'params' => '',
                        'title' => '🤖 AI Engine & Vault',
                    ],
                ],
            ],
            'customer-list' => [
                'icon' => 'users',
                'route_name' => 'customers',
                'params' => '',
                'title' => 'Customers',
            ],
            'astrologer-list' => [
                'icon' => 'target',
                'title' => $professionTitle,
                'sub_menu' => [
                    'block' . $professionTitle => [
                        'icon' => 'x',
                        'route_name' => 'blockAstrologer',
                        'params' => '',
                        'title' => 'Block' . $professionTitle,
                    ],
                    'manage' . $professionTitle => [
                        'icon' => 'folder',
                        'route_name' => 'astrologers',
                        'params' => '',
                        'title' => 'Manage' . $professionTitle,
                    ],
                    'pendingrequests' => [
                        'icon' => 'circle',
                        'route_name' => 'pending-requests',
                        'params' => '',
                        'title' => 'Pending Requests',
                    ],
                    'reviews' => [
                        'icon' => 'star',
                        'route_name' => 'astrologerReview',
                        'params' => '',
                        'title' => 'Reviews',
                    ],
                    'gifts' => [
                        'icon' => 'gift',
                        'route_name' => 'gifts',
                        'params' => '',
                        'title' => 'Gifts',
                    ],
                    'skills' => [
                        'icon' => 'pocket',
                        'route_name' => 'skills',
                        'params' => '',
                        'title' => 'Skills',
                    ],
                    'categories' => [
                        'icon' => 'asterisk',
                        'route_name' => 'astrologerCategories',
                        'params' => '',
                        'title' => 'Categories',
                    ],
                    'commissionn' => [
                        'icon' => 'indian-rupee',
                        'route_name' => 'commissions',
                        'params' => '',
                        'title' => 'Commissionn rate for calls/ chats',
                    ],
                    'profileboost' => [
                        'icon' => 'rocket',
                        'route_name' => 'profile-list',
                        'params' => '',
                        'title' => 'Profile Boost',
                    ],
                ],
            ],
            'astroMall' => [
                'icon' => 'layers',
                'title' => 'Astroshop',
                'sub_menu' => [
                    'productCategory' => [
                        'icon' => '',
                        'route_name' => 'productCategories',
                        'params' => '',
                        'title' => 'Product Categories',
                    ],
                    'product' => [
                        'icon' => '',
                        'route_name' => 'products',
                        'params' => '',
                        'title' => 'Products',
                    ],
                    'order' => [
                        'icon' => '',
                        'route_name' => 'orders',
                        'params' => '',
                        'title' => 'Orders',
                    ],
                ],
            ],
            'daily-horoscope' => [
                'icon' => 'book-open',
                'title' => 'Horoscope',
                'sub_menu' => [
                    'dailyHoroScope' => [
                        'icon' => '',
                        'route_name' => 'dailyHoroscope',
                        'params' => '',
                        'title' => 'Daily HoroScope',
                    ],
                    // 'dailyHoroScopeInsight' => [
                    //     'icon' => '',
                    //     'route_name' => 'dailyHoroscopeInsight',
                    //     'params' => "",
                    //     'title' => 'Horoscope Insights',
                    // ],
                    'horoscope' => [
                        'icon' => '',
                        'route_name' => 'horoscope',
                        'params' => '',
                        'title' => 'Weekly & Yearly Horoscope',
                    ],
                    'horoscopeFeedback' => [
                        'icon' => '',
                        'route_name' => 'horoscopeFeedback',
                        'params' => '',
                        'title' => 'Horoscope Feedback',
                    ],
                ]
            ],
            'puja' => [
                'icon' => 'aperture',
                'title' => 'Puja',
                'sub_menu' => [
                    'categories' => [
                        'icon' => 'asterisk',
                        'route_name' => 'puja-categories-list',
                        'params' => '',
                        'title' => 'Categories',
                    ],
                    'subcategories' => [
                        'icon' => 'asterisk',
                        'route_name' => 'puja-subcategories-list',
                        'params' => '',
                        'title' => 'Sub Categories',
                    ],
                    'pujapackage' => [
                        'icon' => 'package',
                        'route_name' => 'package-list',
                        'params' => '',
                        'title' => 'Puja Package',
                    ],
                    'puja' => [
                        'icon' => 'box',
                        'route_name' => 'puja-list',
                        'params' => '',
                        'title' => 'Puja',
                    ],
                    'pujafaqs' => [
                        'icon' => 'align-justify',
                        'route_name' => 'puja-faq-list',
                        'params' => '',
                        'title' => 'Puja Faq',
                    ],
                    'pujaorder' => [
                        'icon' => 'shopping-cart',
                        'route_name' => 'puja-order-list',
                        'params' => '',
                        'title' => 'Puja Order',
                    ],
                ],
            ],
            'course' => [
                'icon' => 'book',
                'title' => 'Course',
                'sub_menu' => [
                    'categories' => [
                        'icon' => 'asterisk',
                        'route_name' => 'course-categories-list',
                        'params' => '',
                        'title' => 'Categories',
                    ],
                    'course' => [
                        'icon' => 'package',
                        'route_name' => 'CourseList-list',
                        'params' => '',
                        'title' => 'Course',
                    ],
                    'chapters' => [
                        'icon' => 'package',
                        'route_name' => 'course-chapter-list',
                        'params' => '',
                        'title' => 'Chapters',
                    ],
                    'courseorders' => [
                        'icon' => 'shopping-cart',
                        'route_name' => 'courseOrderList',
                        'params' => '',
                        'title' => 'Course Orders',
                    ],
                ],
            ],
            'blog-list' => [
                'icon' => 'edit',
                'route_name' => 'blogs',
                'params' => '',
                'title' => 'Blogs',
            ],
            'news' => [
                'icon' => 'airplay',
                'route_name' => 'astroguruNews',
                'params' => '',
                'title' => 'News',
            ],
            'adsVideo' => [
                'icon' => 'video',
                'route_name' => 'adsVideos',
                'params' => '',
                'title' => 'Videos',
            ],
            'banner-list' => [
                'icon' => 'image',
                'route_name' => 'banners',
                'params' => '',
                'title' => 'Banner Management',
            ],
            'stories' => [
                'icon' => 'play-circle',
                'route_name' => 'story-list',
                'params' => '',
                'title' => 'Stories',
            ],
            'notification-list' => [
                'icon' => 'bell-ring',
                'route_name' => 'notifications',
                'params' => '',
                'title' => 'Notifications',
            ],
            'earning' => [
                'icon' => 'dollar-sign',
                'title' => 'Support Management',
                'sub_menu' => [
                    'ticket' => [
                        'icon' => 'twitch',
                        'route_name' => 'tickets',
                        'params' => '',
                        'title' => 'Support Tickets',
                    ],
                    'faqs' => [
                        'icon' => 'list',
                        'route_name' => 'helpSupport',
                        'params' => '',
                        'title' => 'FAQs',
                    ],
                ],
            ],
            'earning' => [
                'icon' => 'dollar-sign',
                'title' => 'Earnings',
                'sub_menu' => [
                    'withdrawl' => [
                        'icon' => 'dollar-sign',
                        'route_name' => 'withdrawalRequests',
                        'params' => '',
                        'title' => 'Withdrawal Requests',
                    ],
                    'withdrawal-methods' => [
                        'icon' => 'activity',
                        'route_name' => 'withdrawalMethods',
                        'params' => '',
                        'title' => 'Withdrawal Methods',
                    ],
                    'withdrawal-methods' => [
                        'icon' => 'history',
                        'route_name' => 'walletHistory',
                        'params' => '',
                        'title' => 'Wallet History',
                    ],
                ],
            ],
            'report' => [
                'icon' => 'file',
                'title' => 'Reports',
                'sub_menu' => [
                    'callHistory' => [
                        'icon' => 'phone',
                        'route_name' => 'callHistory',
                        'params' => '',
                        'title' => 'Call History',
                    ],
                    'chatHistory' => [
                        'icon' => 'chat',
                        'route_name' => 'chatHistory',
                        'params' => '',
                        'title' => 'Chat History',
                    ],
                    'partnerWiseEarning' => [
                        'icon' => 'doller',
                        'route_name' => 'partnerWiseEarning',
                        'params' => '',
                        'title' => 'PartnerWise Earning',
                    ],
                    'orderRequest' => [
                        'icon' => 'doller',
                        'route_name' => 'orderrequest',
                        'params' => '',
                        'title' => 'Order Request',
                    ],
                    'reportRequest' => [
                        'icon' => 'doller',
                        'route_name' => 'reportrequest',
                        'params' => '',
                        'title' => 'Report Request',
                    ],
                    'kundali' => [
                        'icon' => 'files',
                        'route_name' => 'kundaliearning',
                        'params' => '',
                        'title' => 'Kundali Earning',
                    ],
                    'exotel-report-list' => [
                        'icon' => 'phone',
                        'route_name' => 'exotel-report-list',
                        'params' => '',
                        'title' => 'Exotel Call History',
                    ],
                ],
            ],
            'master-page' => [
                'icon' => 'book-open',
                'title' => 'Master Settings',
                'sub_menu' => [
                    'customer-profile' => [
                        'icon' => '',
                        'route_name' => 'customerProfile',
                        'params' => '',
                        'title' => 'Customer Profile',
                    ],
                    'horor-scope-sign-list' => [
                        'icon' => '',
                        'route_name' => 'horoscopeSigns',
                        'params' => '',
                        'title' => 'HoroScope Signs',
                    ],
                    'reports' => [
                        'icon' => 'folder',
                        'route_name' => 'reportTypes',
                        'params' => '',
                        'title' => 'Report Type',
                    ],
                    'recharge-amount' => [
                        'icon' => '',
                        'route_name' => 'rechargeAmount',
                        'params' => '',
                        'title' => 'Recharge Amount',
                    ],
                    'kundali-prices' => [
                        'icon' => '',
                        'route_name' => 'kundali-prices',
                        'params' => '',
                        'title' => 'Kundali Price',
                    ],
                    'referral-settings' => [
                        'icon' => '',
                        'route_name' => 'referral-settings',
                        'params' => '',
                        'title' => 'Referral Settings',
                    ],
                ],
            ],
            'teammanagement' => [
                'icon' => 'user',
                'title' => 'Team Management',
                'sub_menu' => [
                    'teamRole' => [
                        'icon' => 'equal',
                        'route_name' => 'teamRole',
                        'params' => '',
                        'title' => 'Team Role',
                    ],
                    'team-list' => [
                        'icon' => 'users',
                        'route_name' => 'team-list',
                        'params' => '',
                        'title' => 'Team List',
                    ],
                ],
            ],
            'systemFlag' => [
                'icon' => 'settings',
                'route_name' => 'setting',
                'params' => '',
                'title' => 'General Settings',
            ],
            'appFeedback' => [
                'icon' => 'message-square',
                'route_name' => 'feedback',
                'params' => '',
                'title' => 'Feedback',
            ],
            'pages' => [
                'icon' => 'book-open',
                'route_name' => 'pages',
                'params' => '',
                'title' => 'Page Management',
            ],
            'contactlist' => [
                'icon' => 'contact',
                'route_name' => 'contactlist',
                'params' => '',
                'title' => 'Contact Form',
            ],
        ];
    }

    public static function dynamicMenu()
    {
        $user = auth()->user();
        if (!$user) {
            return collect([]);
        }

        $teamMember = DB::table('teammember')->where('userId', $user->id)->first();

        $cacheKey = $teamMember
            ? 'side_menu_role_' . $teamMember->teamRoleId
            : 'side_menu_full_access';

        // Cache for 24 hours (1440 minutes)
        return Cache::remember($cacheKey, 60 * 24, function () use ($teamMember) {
            // Fetch all pages ordered by displayOrder
            $allPages = DB::table('adminpages')->orderBy('displayOrder', 'ASC')->get();

            $parents = $allPages->whereNull('pageGroup');
            $children = $allPages->whereNotNull('pageGroup')->groupBy('pageGroup');

            $side_menu = [];

            if ($teamMember) {
                $rolePagesIds = DB::table('rolepages')
                    ->where('teamRoleId', $teamMember->teamRoleId)
                    ->pluck('adminPageId')
                    ->toArray();

                // 1. Add Parents that have allowed children
                foreach ($parents as $parent) {
                    // Get children for this parent
                    $myChildren = $children->get($parent->id);

                    if ($myChildren && $myChildren->count() > 0) {
                        // Filter children based on role
                        $allowedChildren = $myChildren->filter(function ($child) use ($rolePagesIds) {
                            return in_array($child->id, $rolePagesIds);
                        })->values();

                        if ($allowedChildren->count() > 0) {
                            // Create a copy/object and assign sub_menu
                            // We convert to stdClass if it isn't already, but DB results are stdClass
                            $menuItem = clone $parent;
                            $menuItem->sub_menu = $allowedChildren->all();  // Convert collection to array
                            $side_menu[] = $menuItem;
                        }
                    }
                }

                // 2. Add Parents that are directly allowed (Standalone pages)
                foreach ($parents as $parent) {
                    if (in_array($parent->id, $rolePagesIds)) {
                        // Check if not already added?
                        // The original code allowed merging, which could duplicate.
                        // If we want to strictly follow original simple merge logic:
                        $side_menu[] = clone $parent;
                    }
                }
            } else {
                // Not a team member - Show all
                foreach ($parents as $parent) {
                    $menuItem = clone $parent;
                    $myChildren = $children->get($parent->id);
                    if ($myChildren) {
                        $menuItem->sub_menu = $myChildren->all();
                    } else {
                        $menuItem->sub_menu = [];
                    }
                    $side_menu[] = $menuItem;
                }
            }

            return collect($side_menu)->sortBy('displayOrder');
        });
    }

    public static function hasDashboardAccess()
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $teamMember = DB::table('teammember')->where('userId', $user->id)->first();

        if (!$teamMember) {
            return true;
        }

        $cacheKey = 'dashboard_access_' . $user->id;  // Use userId since teamRoleId is per team member

        return Cache::remember('dashboard_access_' . $teamMember->teamRoleId, 60 * 24, function () use ($teamMember) {
            return DB::table('rolepages')
                ->where('adminPageId', 1)
                ->where('teamRoleId', $teamMember->teamRoleId)
                ->exists();
        });
    }
}
