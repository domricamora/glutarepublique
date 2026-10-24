<?php

namespace Database\Seeders;

use App\Models\MembershipTier;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Treatment;
use Illuminate\Database\Seeder;

/**
 * Marketing content for the pages beyond the home screen: membership tiers,
 * promotions, journal articles and the legal pages. Copy avoids result guarantees and superiority claims (plan.md §83).
 */
class MarketingContentSeeder extends Seeder
{
    /** slug => [name, tagline, monthly price, benefits, note, featured] */
    public const TIERS = [
        'glow' => [
            'Glow',
            'A monthly facial habit for steady, visible care.',
            2990,
            [
                'One Hydra Facial every month',
                '10% off all facial treatments',
                'Birthday treatment upgrade',
                'Priority booking windows',
            ],
            'Best for keeping up results after a treatment plan.',
            false,
        ],
        'premium' => [
            'Premium',
            'A monthly allowance, doctor reviews and member pricing.',
            6500,
            [
                'A monthly treatment allowance of 7,500 pesos',
                '15% off treatments and products',
                'Quarterly skin review with a doctor',
                'Priority booking and member events',
                'Two guest passes a year',
            ],
            'Unused allowance carries over for 30 days.',
            true,
        ],
        'signature' => [
            'Signature',
            'Everything in Premium, plus plan reviews and concierge booking.',
            12500,
            [
                'A monthly allowance of 15,000 pesos',
                '20% off treatments and products',
                'Quarterly injectables plan review',
                'Dedicated line and same-day appointment requests',
                'An annual body or wellness session',
            ],
            'Injectable and clinical treatments still begin with a consultation.',
            false,
        ],
    ];

    /**
     * slug => [title, summary, description, details, badge, ends_on, treatment, image]
     *
     * The clinic's packages and promotions, from its own flyers (supplied by the
     * owner, 2026-10-23). Prices are as printed. The flyers give no end dates,
     * so none are set; the office adds them in Promotions & packages.
     */
    public const PROMOTIONS = [
        'nad-bermonths-promo' => [
            'NAD+ IV Therapy: Bermonths promo',
            'A session of NAD+ IV therapy for 5,499 pesos. Buy 8 sessions and get 2 free.',
            'NAD+ (nicotinamide adenine dinucleotide) is a coenzyme found in every cell of the body, and its levels fall with age. The drip is given by our nurses after a consultation.',
            ['5,499 pesos per session', 'Buy 8 sessions, get 2 free'],
            'Bermonths promo',
            null,
            'nad-iv-therapy',
            '/media/photos/flyers/nad-bermonths-promo.jpg',
        ],
        '90-day-body-slim-down' => [
            '90 Day Body Slim Down',
            'A comprehensive 90-day slimming program for 40,000 pesos. Regular price 60,000 pesos.',
            "Slimming shots, Slimming Dome, Emsculpt and Peach Bottle over 90 days. The slimming shots are prescription injectables, so the program begins with a doctor's assessment.",
            ['4 shots slimming shot (2.5 mg per shot)', '8 shots slimming shot (5.0 mg per shot)', '4 sessions Slimming Dome', '4 sessions Emsculpt', '1 vial Peach Bottle', '40,000 pesos (regular price 60,000 pesos)'],
            'Package',
            null,
            null,
            '/media/photos/flyers/90-day-body-slim-down.jpg',
        ],
        '30-days-slimming-program' => [
            '30 Days Slimming Program',
            'Mounjaro shots with sauna capsule, Luscilipo and RF sessions, the full program for 35,000 pesos.',
            "A 30-day program. Mounjaro (tirzepatide) is a prescription medicine, so the program begins with a doctor's assessment.",
            ['4 shots of Mounjaro pen', '4 sessions Sauna Slimming Capsule', '4 sessions Luscilipo Mesotherapy', '4 sessions RF Treatment', 'The full session: 35,000 pesos'],
            'Package',
            null,
            null,
            '/media/photos/flyers/30-days-slimming-program.jpg',
        ],
        'fat-kill-3-day-sale' => [
            'Fat Kill: 3 Day Sale',
            'Five sessions each of Emslim, Cryo Fat and Meso Lipo for 14,999 pesos.',
            'Three body treatments in one package, offered as a 3 day sale.',
            ['5 sessions of Emslim', '5 sessions of Cryo Fat', '5 sessions of Meso Lipo', '14,999 pesos'],
            '3 day sale',
            null,
            null,
            '/media/photos/flyers/fat-kill.jpg',
        ],
        'ultimate-slimming-package' => [
            'Ultimate Slimming Package',
            'Five sessions each of Mesolipo, Exilift and Emsculpture for 14,999 pesos.',
            'Fat-dissolving injections, skin tightening and body contouring in one package.',
            ['5 sessions Mesolipo', '5 sessions Exilift', '5 sessions Emsculpture', '14,999 pesos'],
            'Package',
            null,
            null,
            '/media/photos/flyers/ultimate-slimming-package.jpg',
        ],
        'slimming-fit-iv-push' => [
            'Slimming Fit IV Push',
            '20 sessions of Vita Boost and 20 sessions of Slimming Drip for 8,888 pesos, shareable by 2 to 3 people.',
            'Forty IV push sessions in one package that two or three people can share.',
            ['20 sessions Vita Boost', '20 sessions Slimming Drip', '8,888 pesos', 'Shareable for 2 to 3 people'],
            'Package',
            null,
            null,
            '/media/photos/flyers/slimming-fit-iv-push.jpg',
        ],
        'get-glow-drip' => [
            'Get Glow Drip',
            'Three sessions each of Platinum Drip, NAD+ and Vita Boost for 7,499 pesos. Shareable.',
            'Nine drip sessions in one package that can be shared.',
            ['3 sessions Platinum Drip', '3 sessions NAD+', '3 sessions Vita Boost', '7,499 pesos', 'Shareable'],
            'Package',
            null,
            'platinum-drip',
            '/media/photos/flyers/get-glow-drip.jpg',
        ],
        'nad-facial-skin-booster' => [
            'NAD+ plus Facial plus Skin Booster',
            'NAD+ with a free facial and a free skin booster, for 12,500 pesos.',
            'NAD+ for energy and cellular repair, a facial that deep-cleans and exfoliates, and a skin booster that hydrates from within.',
            ['NAD+', 'Facial, free', 'Skin booster, free', '12,500 pesos'],
            'Package',
            null,
            'nad-iv-therapy',
            '/media/photos/flyers/nad-facial-skin-booster.jpg',
        ],
        'ultimate-ua-whitening-package' => [
            'Ultimate UA Whitening Package',
            'Ten sessions each of diode UA laser, UA bleach and skin rejuve laser for 9,999 pesos.',
            'An underarm (UA) package: diode laser, bleach and skin rejuvenation laser, ten sessions of each.',
            ['10 sessions Diode UA Laser', '10 sessions UA Bleach', '10 sessions Skin Rejuve Laser', '9,999 pesos'],
            'Package',
            null,
            'diode-laser-hair-removal',
            '/media/photos/flyers/ultimate-ua-whitening.jpg',
        ],
        'aqua-sponge-peel-4-sessions' => [
            'Aqua Sponge Peel: 4 sessions',
            'Four sessions of Aqua Sponge Peel bio-microneedling for 10,000 pesos.',
            'Aqua Sponge Peel is suited to acne-prone skin, hyperpigmentation, uneven tone and texture, fine lines, large pores and keratosis pilaris.',
            ['4 sessions for 10,000 pesos'],
            'Package',
            null,
            'aqua-sponge-peel',
            '/media/photos/flyers/aqua-sponge-peel.jpg',
        ],
        'gluta-drip-packages' => [
            'Gluta Push & Gluta Drip packages: 5+1 and 10+3',
            'Every gluta push and drip comes as a 5+1 or a 10+3 package.',
            'Book five sessions and the sixth is free, or book ten and get three more.',
            ['Bronze: 900 pesos per session, 3,699 pesos for 5+1, 7,299 pesos for 10+3', 'Gold: 1,800 pesos per session, 7,299 pesos for 5+1, 14,499 pesos for 10+3', 'Diamond Drip: 2,500 pesos per session, 9,999 pesos for 5+1, 19,999 pesos for 10+3', 'Platinum Drip: 3,000 pesos per session, 14,000 pesos for 5+1, 26,000 pesos for 10+3'],
            'Package',
            null,
            'gluta-drip-bronze',
            '/media/photos/clinic/cebu-iv-drip.jpg',
        ],
        'lumin-seoul-drip-5-plus-1' => [
            'Lumin Seoul Drip: buy 5 + 1 free',
            'Lumin Seoul Drip for 2,500 pesos a session. Buy 5 sessions, get 1 free.',
            'An all-in-one gluta drip with a powdery white booster.',
            ['2,500 pesos per session', 'Buy 5, get 1 free'],
            'Promo',
            null,
            'lumin-seoul-drip',
            '/media/photos/flyers/lumin-seoul-drip.jpg',
        ],
        'hikari-drip-premium-5-plus-1' => [
            'Hikari Drip Premium: buy 5 + 1 free',
            'Hikari Drip Premium for 2,500 pesos a session. Buy 5 sessions, get 1 free.',
            'A rosy white skin drip for whitening and anti-aging care.',
            ['2,500 pesos per session', 'Buy 5, get 1 free'],
            'Promo',
            null,
            'hikari-drip-premium',
            '/media/photos/flyers/hikari-drip-premium.jpg',
        ],
        'namiko-fusion-drip-packages' => [
            'Namiko Fusion Drip packages',
            'Namiko Fusion Drip for 2,000 pesos a session, 5 sessions for 9,000 pesos or 10 sessions for 15,000 pesos.',
            'Gluta, tranexamic acid, CoQ10, ALA, B vitamins, zinc and collagen in one drip.',
            ['2,000 pesos per session', '5 sessions for 9,000 pesos', '10 sessions for 15,000 pesos'],
            'Promo',
            null,
            'namiko-fusion-drip',
            '/media/photos/flyers/namiko-fusion-drip.jpg',
        ],
        'sakura-kiyoshi-drip-packages' => [
            'Sakura Kiyoshi Drip packages',
            'Sakura Kiyoshi Drip, 5 sessions for 11,499 pesos or 10 sessions for 17,999 pesos.',
            'A drip for radiant, rosy skin with collagen and immune support.',
            ['5 sessions for 11,499 pesos', '10 sessions for 17,999 pesos'],
            'Promo',
            null,
            'sakura-kiyoshi-drip',
            '/media/photos/flyers/sakura-kiyoshi-drip.jpg',
        ],
        'cinderella-drips-5-plus-1' => [
            'Cinderella Drip and Cinderella & Skinny Drip',
            'Cinderella Drip for 2,200 pesos and Cinderella & Skinny Drip for 2,500 pesos a session, each buy 5 + 1.',
            'Two drips, each also sold as a buy 5 + 1 series.',
            ['Cinderella Drip: 2,200 pesos per session, buy 5 sessions + 1 free', 'Cinderella & Skinny Drip: 2,500 pesos per session, buy 5 + 1'],
            'Promo',
            null,
            'cinderella-drip',
            '/media/photos/flyers/cinderella-drips.jpg',
        ],
        'nctf-skin-booster-5000' => [
            'NCTF Skin Booster for 5,000 pesos',
            'NCTF skin booster, 1 vial per session, for 5,000 pesos.',
            'Fillmed NCTF 135 HA for deep hydration, skin elasticity and firmness, fine lines and pigmentation, and radiance.',
            ['5,000 pesos', '1 vial per session'],
            'Promo',
            null,
            'nctf-skin-booster',
            '/media/photos/flyers/nctf-skin-booster.jpg',
        ],
        '12d-hifu-20-off' => [
            '12D HIFU: 20% off',
            '12D HIFU for the whole body and face, now 20% off.',
            'A non-invasive treatment using high-intensity focused ultrasound to lift, tighten and rejuvenate the skin.',
            ['20% off', 'Treatment area: whole body and face'],
            '20% off',
            null,
            '12d-hifu',
            '/media/photos/flyers/12d-hifu.jpg',
        ],
        'thermage-20-off' => [
            'Thermage Skin Tightening: 20% off',
            'Thermage skin tightening, now 20% off.',
            'Thermage can smoothen and tighten skin and give contour for an overall younger-looking appearance.',
            ['20% off'],
            '20% off',
            null,
            'thermage',
            '/media/photos/flyers/thermage.jpg',
        ],
        'vaginal-rejuvenation-30-off' => [
            'Vaginal Rejuvenation: 30% off',
            'Laser vaginal rejuvenation, now 30% off.',
            'Laser vaginal tightening is a non-surgical procedure that uses laser energy to tighten the vaginal canal and rejuvenate the vaginal area.',
            ['30% off'],
            '30% off',
            null,
            'vaginal-rejuvenation',
            '/media/photos/flyers/vaginal-rejuvenation.jpg',
        ],
    ];

    /** slug => [title, category, excerpt, body, takeaways, image, author, read minutes, days ago] */
    public const POSTS = [
        'choosing-your-first-facial' => [
            'Choosing your first facial',
            'Skin care',
            'A facial is not one treatment but a family of them. Here is how to pick the one that suits your skin and your calendar.',
            "Facials differ by how much they exfoliate, how much downtime they involve and how often they should be repeated. A Hydrafacial is the gentlest starting point: cleansing, extraction and serum infusion in about an hour, with no downtime. A Diamond Peel or an Aqua Sponge Peel goes further, exfoliating to refresh tone and texture, and works best as a series spaced a few weeks apart.\n\nThe useful question is not which facial is strongest but which one fits your skin right now. If you have active breakouts, a doctor-led acne programme combined with extraction and home care usually works better than a single brightening facial. If your concern is dullness or pigmentation, a series planned in advance will do more than repeated one-offs.\n\nBring your current routine to the consultation, including prescription creams and anything you use for acne or retinol. Your practitioner needs that picture to advise a plan you can actually follow, and to tell you honestly if the treatment you asked for is not the one you need.\n\nMost clients see a difference after one visit and a clearer difference after a planned series. We will give you the plan in writing, with the number of sessions and the price, before you commit to anything.",
            [
                'Gentle first: a Hydrafacial suits most skin with no downtime',
                'Peels and lasers work as a series, not as a single visit',
                'Bring your current routine, including prescriptions, to the consultation',
            ],
            '/media/photos/facial.jpg',
            'Gluta RepubliQ clinical team',
            4,
            5,
        ],
        'what-happens-in-a-consultation' => [
            'What happens in a consultation',
            'Clinic news',
            'A first visit at Gluta RepubliQ is an assessment, not a sales conversation. Here is how the forty minutes are spent.',
            "We start with what brought you in. Your practitioner asks about your concerns, how long you have had them, what you have tried and what you did not like about it. History matters: medicines, allergies, pregnancy or breastfeeding, recent treatments elsewhere, and any skin or health condition that changes what is safe.\n\nNext comes the assessment. Your skin is examined under good light, sometimes with a lamp or a camera, and your practitioner explains what they see in plain language. You are welcome to photograph anything you want to remember.\n\nThen the plan. Where a treatment is appropriate, you receive the options with prices, the number of sessions, realistic downtime and what aftercare involves. Where we think a treatment is not in your interest, we say so, and where a referral is more appropriate we will help you find one.",
            [
                'Assessment first, treatment second',
                'You leave with a written plan and prices',
                'No treatment is booked on the day unless you ask for it',
            ],
            '/media/photos/consultation.jpg',
            'Gluta RepubliQ clinical team',
            3,
            9,
        ],
        'aftercare-for-injectables' => [
            'Aftercare for injectables: the first 48 hours',
            'Aftercare',
            'Small habits in the first two days make the difference between a smooth result and an anxious week.',
            "For the rest of the day, keep your head up where you can and avoid lying face down. Skip strenuous exercise, saunas, steam rooms and hot yoga for 24 hours: heat and heavy exertion increase flushing and bruising.\n\nDo not massage or press the treated area unless your practitioner asked you to. Clean makeup is fine after 12 hours for most people, and gentle cleansing with cool water is better than a scrub or an active serum for the first two days.\n\nBruising is common and settles in three to seven days. A little tenderness, slight swelling or a mild headache after forehead treatment is also normal. Sleep on your back if you can, and keep to your normal skincare minus exfoliating acids and retinoids for 48 hours.\n\nCall us the same day if you notice severe pain, blanching of the skin, a spreading rash, blurred vision, difficulty swallowing or anything that worries you. We would much rather hear from you early.",
            [
                'No exercise, heat or alcohol for the first 24 hours',
                'Avoid massage and active skincare for 48 hours',
                'Call the clinic the same day about anything that worries you',
            ],
            '/media/photos/botox.jpg',
            'Gluta RepubliQ clinical team',
            4,
            13,
        ],
        'sunscreen-in-the-philippines' => [
            'Sunscreen in the Philippines: what actually matters',
            'Skin care',
            'Between the heat, the humidity and the commute, sunscreen here has to do more than sit in a bag.',
            "Broad spectrum matters more than the number. A sunscreen labelled SPF 50 but PA++ leaves you short on UVA, which is the part that drives pigmentation and ageing. Look for PA+++ or PA++++, or a label that says broad spectrum with a UVA rating.\n\nTexture decides whether you will use it. In this climate, a light gel or fluid for everyday and a heavier cream for air-conditioned days is a practical pair. Two fingers worth for the face and neck, reapplied every two to three hours outdoors, is the aim. Makeup with SPF is a bonus, not your main protection.\n\nTreatments raise the stakes. After a peel, a laser session or any injectable, your skin is more sensitive to the sun and to heat. Sunscreen in the morning, a hat at midday and shade where you can find it will protect the result you paid for.\n\nIf you are unsure which formula suits your skin, bring your current one to your next visit and we will look at it with you.",
            [
                'Broad spectrum with PA+++ or higher beats a big SPF number alone',
                'Reapply every two to three hours when you are outdoors',
                'After a treatment, sun protection protects the result',
            ],
            '/media/photos/skin-closeup.jpg',
            'Gluta RepubliQ clinical team',
            3,
            18,
        ],
        'between-laser-sessions' => [
            'Laser sessions: what to expect between visits',
            'Treatments',
            'Most of the improvement from a laser programme happens in the three weeks after each session, not during it.',
            "A laser session starts a repair process. In the days afterwards your skin may feel warm and look slightly flushed; pigmented areas can darken before they flake away. That is expected, and it is why sessions are spaced three to four weeks apart rather than stacked in one week.\n\nBetween visits, keep the routine boring: gentle cleansing, moisturiser, sunscreen in the morning and nothing new and active. Skip scrubs, waxing, threading and self-tanners on treated areas unless your practitioner says otherwise.\n\nTake a photograph in the same light and at the same distance each week. Progress in pigmentation and texture is gradual, and a set of photos taken the same way is far more reliable than memory on a tired evening.\n\nTell us about any trip, event or holiday planned in the next month, because sun exposure and laser programmes need to be planned around each other.",
            [
                'Sessions are spaced three to four weeks apart on purpose',
                'Keep skincare simple between visits',
                'Weekly photos in the same light show progress that memory misses',
            ],
            '/media/photos/laser.jpg',
            'Gluta RepubliQ clinical team',
            4,
            22,
        ],
        'scalp-health-basics' => [
            'Scalp health basics for thinning hair',
            'Hair',
            'Hair programmes work better when they start with the scalp and with an honest look at the last six months.',
            "Thinning hair has many causes: genetics, hormonal shifts, thyroid function, iron levels, stress, weight loss, and styling or treatment habits that pull and heat the follicle. A consultation is about narrowing that list rather than guessing, and it may include blood tests or a referral.\n\nDay to day, wash when your scalp needs it rather than to a schedule, treat the scalp as skin that deserves care, and avoid tight styles, harsh chemical straightening and high heat close to the root. If your scalp itches, flakes or stings, that is worth treating rather than ignoring.\n\nProgrammes such as PRP or topical treatments are planned over months, not weeks, and progress is judged at three and six months with photographs. Anyone promising a fixed result in a fortnight is not describing how hair cycles work.\n\nStart with an assessment. It is the cheapest part of the journey and the one that decides everything after it.",
            [
                'Thinning hair has several possible causes; assessment narrows them',
                'Scalp comfort and gentle styling support every programme',
                'Judge hair progress at three and six months, with photos',
            ],
            '/media/photos/prp.jpg',
            'Gluta RepubliQ clinical team',
            3,
            27,
        ],
    ];

    /** slug => [title, summary, reviewed days ago, sections] */
    public const PAGES = [
        'privacy-policy' => [
            'Privacy Policy',
            'How Gluta RepubliQ Aesthetics collects, uses, stores and protects personal information, in line with the Data Privacy Act of 2012 (Republic Act 10173).',
            0,
            [
                ['heading' => 'Who we are', 'paragraphs' => [
                    'Gluta RepubliQ Aesthetics operates aesthetic and wellness clinics in Cebu, San Pedro and Bacoor. We are the personal information controller for the information described in this policy.',
                    'Questions about privacy, or a request to exercise your rights, can be sent to the clinic email address on our contact page or raised at the branch where you are treated. We aim to respond within fifteen working days.',
                ]],
                ['heading' => 'What we collect', 'paragraphs' => [
                    'Identification and contact details you give us: your name, mobile number, email address, preferred branch and, where relevant, your birth date and gender.',
                    'Appointment and commercial records: treatments booked and attended, practitioner notes, prices paid, packages or memberships, and messages you send us.',
                    'Health information you share with our practitioners: your concerns, medical history, medicines, allergies, pregnancy or breastfeeding status, and consent records. This is sensitive personal information and we treat it with additional care.',
                    'Technical information from this website: the pages you visit, the campaign or link that brought you here, your approximate location from your IP address, and the device type you used.',
                ]],
                ['heading' => 'Why we collect it', 'paragraphs' => [
                    'To respond to your enquiry and arrange appointments you ask for.',
                    'To provide care safely: assessing your skin, planning treatment, recording what was done, and following up.',
                    'To run the clinic: scheduling, reminders, billing and receipts, membership administration, and inventory of products used.',
                    'To improve our services and, where you have agreed, to send offers and updates. Marketing consent is separate from the consent you give for treatment and can be withdrawn at any time.',
                ]],
                ['heading' => 'Legal basis', 'paragraphs' => [
                    'Where you are a client or patient, we rely mainly on the performance of our agreement with you and on the provision of health care, together with your consent for sensitive personal information and for marketing.',
                    'Where the law requires records to be kept, for example for tax or regulatory purposes, we process information to comply with those obligations. We also have a legitimate interest in keeping our clinics secure and our services working properly.',
                ]],
                ['heading' => 'Who we share it with', 'paragraphs' => [
                    'Our practitioners and staff who need the information to care for you or to run your visit.',
                    'Suppliers who help us operate, such as our booking and hosting providers and our payment channels, under contracts that limit their use of the information to the service they provide.',
                    'Professional advisers, auditors and, where we are required to do so, government agencies such as the Bureau of Internal Revenue and the Department of Health.',
                    'We do not sell personal information, and we do not share health information for advertising.',
                ]],
                ['heading' => 'How long we keep it', 'paragraphs' => [
                    'Enquiry records that do not become client records are kept for two years, then deleted.',
                    'Clinical records are kept for as long as the law and professional guidance require, which is generally at least ten years after your last visit, and longer for records involving minors.',
                    'Marketing contact details are kept until you withdraw consent or ask to be removed.',
                ]],
                ['heading' => 'How we protect it', 'paragraphs' => [
                    'Access to clinical information is limited by role, and staff accounts require strong passwords and, where available, a second factor. Activity on client records is logged.',
                    'Data is stored on access-controlled systems, transmitted over encrypted connections, and backed up. We review access and retention regularly and investigate any suspected breach.',
                    'If a breach occurs that is likely to give rise to serious harm, we will notify you and the National Privacy Commission as required by law.',
                ]],
                ['heading' => 'Your rights', 'paragraphs' => [
                    'You have the right to be informed, to object to processing, to access your information, to correct it, to have it erased or blocked in the circumstances allowed by law, to claim damages, to data portability, and to lodge a complaint with the National Privacy Commission.',
                    'To exercise any of these rights, contact us with your name, the branch you attend and what you would like us to do. We may ask for proof of identity before acting, and we may need to keep certain records where the law requires it.',
                ]],
                ['heading' => 'Cookies and attribution', 'paragraphs' => [
                    'This website uses a small session cookie so that forms work and so that we can remember which page you came from when you send an enquiry. It does not build a profile of you across other websites.',
                    'Where you arrive from a campaign link, we record the campaign, the landing page and the referrer alongside your enquiry so that we can answer you in context.',
                ]],
                ['heading' => 'Changes to this policy', 'paragraphs' => [
                    'We review this policy when our services or the law change. The version published here applies from the date shown at the end of the page, and material changes will be announced on this website.',
                ]],
            ],
        ],
        'terms' => [
            'Terms of Service',
            'The terms that apply when you use this website, book an appointment with Gluta RepubliQ Aesthetics, or hold a membership.',
            0,
            [
                ['heading' => 'Using this website', 'paragraphs' => [
                    'This website is provided so that you can learn about our treatments and book appointments.',
                    'Please do not misuse the site, attempt to reach data that is not yours, or submit information that is false or that belongs to someone else.',
                ]],
                ['heading' => 'Bookings and confirmations', 'paragraphs' => [
                    'A booking request is confirmed only when you receive a confirmation with a reference number. Please arrive ten minutes early and tell us if you are running late; a late arrival may need to be shortened or moved so that other clients are not kept waiting.',
                    'We may need to change a practitioner or a room for your visit. We will tell you as soon as we can, and you are free to move the appointment instead.',
                ]],
                ['heading' => 'Fees, deposits and payments', 'paragraphs' => [
                    'Prices published on this site are starting prices for one session in Philippine pesos and include professional fees unless a treatment page says otherwise. A consultation may add a separate fee, which we credit against treatment where we advertise it.',
                    'Where a package or series is booked, the price and validity period are stated on the promotion page and on your receipt. Packages are transferable to another branch but not to another person without our agreement.',
                ]],
                ['heading' => 'Cancellations and no-shows', 'paragraphs' => [
                    'Please give us at least 24 hours notice to move or cancel a visit. Repeated late cancellations or no-shows may mean we ask for a deposit to hold future bookings.',
                    'Injected and clinical treatments may have their own cancellation or deposit terms. These are explained before you book.',
                ]],
                ['heading' => 'Treatment suitability and consent', 'paragraphs' => [
                    'Every treatment begins with an assessment. Your practitioner may decide that a treatment is not suitable for you, may ask for more information, or may refer you to another professional. You can stop or decline a treatment at any time.',
                    'You are responsible for giving us accurate health information, including medicines, allergies, pregnancy or breastfeeding, and recent treatment elsewhere, so that we can advise you safely.',
                ]],
                ['heading' => 'Nothing here is medical advice', 'paragraphs' => [
                    'The content on this website, including journal articles and treatment descriptions, is general information for an adult audience in the Philippines. It is not a diagnosis, a prescription or a substitute for a consultation.',
                    'No result is guaranteed. Outcomes vary with your skin, your history and how closely you follow the aftercare you are given.',
                ]],
                ['heading' => 'Memberships', 'paragraphs' => [
                    'Memberships are monthly, start on the day of purchase and renew until you cancel. Benefits listed for a tier apply at the branch or branches named on the membership page, and unused allowances carry over for the period stated on that tier.',
                    'If prices change, we will tell you before your next renewal and you may cancel without penalty.',
                ]],
                ['heading' => 'Content and intellectual property', 'paragraphs' => [
                    'The text, layout and photography on this site belong to the clinic or its licensors. Please do not reuse them commercially without written permission.',
                    "Photographs on this site are the clinic's own or come from royalty free libraries, credited where the licence requires it.",
                ]],
                ['heading' => 'Limits of liability', 'paragraphs' => [
                    'To the extent the law allows, we are not liable for indirect or consequential loss arising from use of this website or from a booking made through it. Nothing in these terms limits any right you have under Philippine consumer and health laws.',
                ]],
                ['heading' => 'Governing law and changes', 'paragraphs' => [
                    'These terms are governed by the laws of the Republic of the Philippines, and disputes are subject to the courts of the city where the branch you used is located.',
                    'We may update these terms; the version published here applies from the date shown at the end of the page.',
                ]],
            ],
        ],
        'data-privacy-notice' => [
            'Data Privacy Notice',
            'What you agree to when you give us your information at the clinic, and the choices you keep.',
            0,
            [
                ['heading' => 'Our commitment', 'paragraphs' => [
                    'Your health information is sensitive personal information under the Data Privacy Act of 2012. We collect only what we need to care for you, we share it only with the people involved in your care and the suppliers that make the clinic work, and we keep it only as long as the law requires.',
                ]],
                ['heading' => 'What you agree to when you book', 'paragraphs' => [
                    'When you send an enquiry or book an appointment, you agree that we may contact you about that enquiry by SMS, phone or email, and that we may keep a record of the visit and of the care you receive.',
                    'Marketing messages are a separate choice. You can tick or untick that box at any time, and saying no does not affect your treatment or any enquiry you send us.',
                ]],
                ['heading' => 'Sensitive personal information', 'paragraphs' => [
                    'Before a treatment we ask about your health: your concerns, medical history, medicines, allergies, pregnancy or breastfeeding, and previous aesthetic or medical treatments. We ask because it changes what is safe for you.',
                    'This information is stored with your clinical record, is visible only to practitioners and staff who need it, and is never used for advertising.',
                ]],
                ['heading' => 'Consent and how to withdraw it', 'paragraphs' => [
                    'You give consent for a specific treatment in writing or on screen at the clinic, after your practitioner has explained what it involves, what the realistic downtime is, and what alternatives exist.',
                    'You can withdraw consent for future treatment or for marketing at any time by telling any branch or writing to our clinic email. Withdrawing consent does not affect records we are required to keep, and it does not apply to care already provided.',
                ]],
                ['heading' => 'Sharing for your care', 'paragraphs' => [
                    'With your agreement, we may share parts of your record with another clinic, hospital or laboratory involved in your care, or with a doctor you ask us to coordinate with.',
                    'Where we are required by law to report to a government agency, we will tell you what we reported unless the law prohibits it.',
                ]],
                ['heading' => 'Retention and security', 'paragraphs' => [
                    'We keep clinical records for at least ten years after your last visit, and longer where the law requires, so that future practitioners can see your history. Afterwards they are securely destroyed.',
                    'Records are held in access-controlled systems with encrypted connections and backups. Access is logged, and we investigate any suspected breach and notify you where the law requires it.',
                ]],
                ['heading' => 'Your rights', 'paragraphs' => [
                    'You can ask what information we hold about you, ask for a copy, ask us to correct anything wrong, ask us to erase or block information where the law allows, object to particular processing, and ask for your information in a portable format.',
                    'You can also complain to the National Privacy Commission. Our team will help you exercise any of these rights; ask at the branch or write to the clinic email on our contact page.',
                ]],
            ],
        ],
    ];

    public function run(): void
    {
        $organization = Organization::where('slug', config('clinic.organization'))->firstOrFail();
        $org = ['organization_id' => $organization->id];

        $sort = 0;
        // The tiers were demo content and the clinic sells no membership yet
        // (2026-10-24), so only the test suite gets them; the site hides
        // membership until an active tier exists.
        foreach (app()->runningUnitTests() ? self::TIERS : [] as $slug => [$name, $tagline, $price, $benefits, $note, $featured]) {
            MembershipTier::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'name' => $name, 'tagline' => $tagline, 'price_monthly' => $price, 'benefits' => $benefits,
                'note' => $note, 'is_featured' => $featured, 'sort' => $sort++,
            ]);
        }

        // Like the menu, the offers are the office's once loaded: they are only
        // written when there are none, so an offer switched off stays off.
        $sort = 0;
        $promotions = Promotion::withoutGlobalScopes()->where($org)->doesntExist() ? self::PROMOTIONS : [];
        foreach ($promotions as $slug => [$title, $summary, $description, $details, $badge, $endsOn, $treatment, $image]) {
            Promotion::withoutGlobalScopes()->firstOrCreate($org + ['slug' => $slug], [
                'title' => $title,
                'summary' => $summary,
                'description' => $description,
                'details' => $details,
                'badge' => $badge,
                'ends_on' => $endsOn,
                'treatment_id' => $treatment ? Treatment::withoutGlobalScopes()->where($org + ['slug' => $treatment])->value('id') : null,
                'image' => $image,
                'is_active' => true,
                'sort' => $sort++,
            ]);
        }

        foreach (self::POSTS as $slug => [$title, $category, $excerpt, $body, $takeaways, $image, $author, $minutes, $daysAgo]) {
            Post::withoutGlobalScopes()->firstOrCreate($org + ['slug' => $slug], [
                'title' => $title,
                'category' => $category,
                'excerpt' => $excerpt,
                'body' => $body,
                'takeaways' => $takeaways,
                'image' => $image,
                'author_name' => $author,
                'read_minutes' => $minutes,
                'published_at' => now()->subDays($daysAgo),
            ]);
        }

        foreach (self::PAGES as $slug => [$title, $summary, $reviewedDaysAgo, $sections]) {
            Page::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'title' => $title,
                'summary' => $summary,
                'sections' => $sections,
                'reviewed_on' => now()->subDays($reviewedDaysAgo),
            ]);
        }
    }
}
