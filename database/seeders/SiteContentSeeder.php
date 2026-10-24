<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Faq;
use App\Models\Organization;
use App\Models\Specialist;
use App\Models\Testimonial;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Database\Seeder;

/**
 * Gluta RepubliQ Aesthetics Site Content Seeder.
 */
class SiteContentSeeder extends Seeder
{
    public const CATEGORIES = [
        'facials' => ['Facials', 'Classic and advanced facials, from the Signature Facial Cleansing to Hydrafacial, Black Doll and exosome facials.', 'clinic/cebu-facial.jpg'],
        'iv-drips' => ['Gluta and IV Drips', 'Gluta push and drip in four strengths, signature drips, NAD+ therapy and add-ons, given in the drip lounge.', 'clinic/cebu-drip-lounge.jpg'],
        'injectables' => ['Botox, Fillers and Threads', 'Botox by area, fillers, thread procedures and the Hiko nose lift.', 'flyers/lip-fillers.jpg'],
        'skin-boosters' => ['Skin Boosters and Regenerative', 'Skin boosters, PRP, acne scar subcision, panda eye, melasma and anti-aging treatments, by appointment only.', 'clinic/cebu-facial-closeup.jpg'],
        'lifting' => ['Lifting and Tightening', '9D and 12D HIFU, Thermalift FLX, Exilift Ultra 360, Thermage and plasma lift, without surgery.', 'asian-dermatologist-consultation.jpg'],
        'laser' => ['Laser Treatments', 'Fotona SP Dynamis laser, diode laser hair removal, warts removal and vaginal rejuvenation.', 'clinic/cebu-carbon-laser.jpg'],
        'slimming' => ['Slimming and Body', 'Cryolipolysis, Emsculpture, Meso-Lipo, Lemon Bottle and Peach Bottle, and the weight loss program.', 'films/emsculpt.jpg'],
        'spmu' => ['Semi-Permanent Makeup and Lashes', 'Brows, Aqua Lips, eyeliner, BB glow, skin camouflage and scalp micropigmentation, plus lash lift and extensions.', 'results/misty-brows.jpg'],
    ];

    /**
     * The clinic's menu, transcribed from its own collaterals (price posters and
     * promo flyers supplied by the owner, 2026-10-23). Names and prices are as
     * printed. A price of 0 means the poster lists the service without a price
     * ("by appointment only"): the site shows "Price on consultation" and the
     * register leaves it off until the office sets one. Where a service has a
     * price list, the list price is its lowest line, shown as "from".
     * Durations are not on the posters; they are booking estimates.
     *
     * slug => [category, subcategory, name, summary, minutes, price, image, sessions, featured, price list [label, price, unit]]
     */
    public const TREATMENTS = [
        // Facial Treatment
        'signature-facial' => ['facials', 'Facial Treatments', 'Signature Facial Cleansing', 'A cleansing facial for congested, dull or tired skin.', 45, 400, 'clinic/cebu-facial.jpg', 'Monthly', false, []],
        'gentlemans-facial' => ['facials', 'Facial Treatments', "Gentleman's Facial", "A deep-cleansing facial planned around men's skin.", 45, 750, 'facial.jpg', 'Monthly', false, []],
        'teen-facial' => ['facials', 'Facial Treatments', 'Teen Facial', 'A gentle cleansing facial for young, breakout-prone skin.', 45, 699, 'acne.jpg', 'Monthly', false, []],
        'anti-ageing-facial' => ['facials', 'Facial Treatments', 'Anti-Ageing Facial', 'A nourishing facial focused on hydration and the look of fine lines.', 60, 1200, 'asian-hydraglow-facial.jpg', 'Monthly', false, []],
        '24k-gold-luxury-facial' => ['facials', 'Facial Treatments', '24K Gold Luxury Facial', 'A pampering facial with a 24K gold treatment for a soft, radiant finish.', 60, 1500, 'hydrafacial.jpg', 'Monthly', false, []],
        'skin-brightening-facial-lhm' => ['facials', 'Facial Treatments', 'Skin Brightening Facial + LHM Bio Active Light', 'A brightening facial finished with LHM bio active light.', 60, 800, 'clinic/cebu-facial-closeup.jpg', 'Monthly', false, []],
        'diamond-peel-with-facial' => ['facials', 'Facial Treatments', 'Diamond Peel with Facial', 'Diamond peel exfoliation followed by a cleansing facial.', 60, 800, 'peel.jpg', 'Monthly', false, []],
        'whitening-facial-lhm' => ['facials', 'Facial Treatments', 'Whitening Facial with LHM Bio Active Light', 'A whitening facial finished with LHM bio active light.', 60, 750, 'facial.jpg', 'Monthly', false, []],
        'acne-treatment-facial-lhm' => ['facials', 'Facial Treatments', 'Acne Treatment Facial with LHM Bio Active Light', 'A clarifying facial for acne-prone skin, finished with LHM bio active light.', 60, 750, 'acne.jpg', 'As advised', false, []],
        'back-facial' => ['facials', 'Facial Treatments', 'Back Facial', 'Cleansing, exfoliation and extraction for the back.', 60, 1000, 'skin-closeup.jpg', 'Monthly', false, []],
        'pimple-injection' => ['facials', 'Facial Treatments', 'Pimple Injection', 'An injection for a single inflamed pimple, priced per piece.', 15, 100, 'clinic/cebu-facial-closeup.jpg', 'Per piece', false, []],
        // Advance Facial Treatment
        'hydra-facial' => ['facials', 'Advanced Facial Treatments', 'Hydrafacial', 'Cleansing, gentle exfoliation, extraction and serum infusion in one visit.', 60, 1500, 'clinic/cebu-facial.jpg', 'Monthly', true, []],
        'collagen-infusion-facial' => ['facials', 'Advanced Facial Treatments', 'Collagen Infusion Facial', 'An advanced facial that infuses collagen for hydrated, plump-looking skin.', 60, 2500, 'hydrafacial.jpg', 'Monthly', false, []],
        'black-doll-facial' => ['facials', 'Advanced Facial Treatments', 'Black Doll Facial', 'An advanced carbon facial that deep-cleans pores and refreshes tired skin.', 60, 2500, 'clinic/cebu-carbon-peel.jpg', 'Monthly', false, []],
        'aqua-sponge-peel' => ['facials', 'Advanced Facial Treatments', 'Aqua Sponge Peel Facial', 'Bio-microneedling with spicules for acne-prone skin, dark spots, uneven texture and large pores.', 60, 2500, 'flyers/aqua-sponge-peel.jpg', '4 sessions', false, [['Per session', 2500, null], ['4 sessions', 10000, null]]],
        // Listed on the poster under a second "Panda Eyes Treatment" heading.
        'exosome-facial' => ['facials', 'Exosome Facials', 'Exosome Facial', 'An exosome facial for dull, dry, sensitive or stressed-looking skin.', 60, 8000, 'booster.jpg', 'As advised', false, []],
        'premium-exosome-facial' => ['facials', 'Exosome Facials', 'Premium Exosome Facial', 'The premium exosome facial, for skin that needs more intensive regenerative care.', 60, 15000, 'flyers/asce-exosomes.jpg', 'As advised', false, []],

        // Gluta Push & Gluta Drip
        'gluta-drip-bronze' => ['iv-drips', 'Gluta Push and Gluta Drip', 'Bronze Gluta Drip', 'Gluta push or drip with vitamins and antioxidants for brighter-looking skin.', 45, 900, 'clinic/cebu-iv-drip.jpg', '5+1 or 10+3 packages', true, [['Per session', 900, null], ['5+1 package', 3699, null], ['10+3 package', 7299, null]]],
        'gluta-drip-gold' => ['iv-drips', 'Gluta Push and Gluta Drip', 'Gold Gluta Drip', 'A higher-dose gluta push or drip for a brighter, healthy glow.', 45, 1800, 'clinic/cebu-drip-lounge.jpg', '5+1 or 10+3 packages', false, [['Per session', 1800, null], ['5+1 package', 7299, null], ['10+3 package', 14499, null]]],
        'diamond-drip' => ['iv-drips', 'Gluta Push and Gluta Drip', 'Diamond Drip', 'A premium gluta drip with added antioxidants for skin radiance.', 60, 2500, 'clinic/cebu-drip-lounge-2.jpg', '5+1 or 10+3 packages', false, [['Per session', 2500, null], ['5+1 package', 9999, null], ['10+3 package', 19999, null]]],
        'platinum-drip' => ['iv-drips', 'Gluta Push and Gluta Drip', 'Platinum Drip', "The clinic's top-tier gluta drip.", 60, 3000, 'clinic/cebu-lounge.jpg', '5+1 or 10+3 packages', false, [['Per session', 3000, null], ['5+1 package', 14000, null], ['10+3 package', 26000, null]]],
        'drip-add-ons' => ['iv-drips', 'Add-ons', 'Gluta Drip Add-ons', 'Boosters added to a gluta push or drip.', 15, 100, 'clinic/cebu-iv-drip.jpg', 'With any drip', false, [['Collagen Infusion', 500, null], ['Placenta Infusion', 500, null], ['Vitamin C Infusion', 100, null], ['Slimming', 500, null], ['Athletic / B Complex', 200, null], ['NAD+', 1000, null], ['Stemcell', 850, null]]],
        // Signature drips, from the promo flyers
        'lumin-seoul-drip' => ['iv-drips', 'Signature Drips', 'Lumin Seoul Drip', 'An all-in-one gluta drip with a powdery white booster, for a hydrated, even glow.', 60, 2500, 'flyers/lumin-seoul-drip.jpg', 'Buy 5, get 1 free', false, [['Per session', 2500, null]]],
        'hikari-drip-premium' => ['iv-drips', 'Signature Drips', 'Hikari Drip Premium', 'A rosy white skin drip for whitening and anti-aging care.', 60, 2500, 'flyers/hikari-drip-premium.jpg', 'Buy 5, get 1 free', false, [['Per session', 2500, null]]],
        'namiko-fusion-drip' => ['iv-drips', 'Signature Drips', 'Namiko Fusion Drip', 'Gluta, tranexamic acid, CoQ10, ALA, B vitamins, zinc and collagen in one drip.', 60, 2000, 'flyers/namiko-fusion-drip.jpg', '5 or 10 session packages', false, [['Per session', 2000, null], ['5 sessions', 9000, null], ['10 sessions', 15000, null]]],
        'sakura-kiyoshi-drip' => ['iv-drips', 'Signature Drips', 'Sakura Kiyoshi Drip', 'A drip for radiant, rosy skin with collagen and immune support, sold in 5 or 10 session packages.', 60, 11499, 'flyers/sakura-kiyoshi-drip.jpg', '5 or 10 session packages', false, [['5 sessions', 11499, null], ['10 sessions', 17999, null]]],
        'cinderella-drip' => ['iv-drips', 'Signature Drips', 'Cinderella Drip', 'A gluta drip, per session or as a buy 5 + 1 free series.', 60, 2200, 'flyers/cinderella-drips.jpg', 'Buy 5 sessions, get 1 free', false, [['Per session', 2200, null]]],
        'cinderella-skinny-drip' => ['iv-drips', 'Signature Drips', 'Cinderella & Skinny Drip', 'The Cinderella drip with the Skinny drip, per session or as a buy 5 + 1 series.', 60, 2500, 'flyers/cinderella-drips.jpg', 'Buy 5 + 1', false, [['Per session', 2500, null]]],
        // NAD+, also listed under Anti-Aging / Fountain of Youth Program
        'nad-iv-therapy' => ['iv-drips', 'NAD+ Therapy', 'NAD+ IV Therapy', 'NAD+ (nicotinamide adenine dinucleotide) as a drip or injectable, using NovaNAD 500 mg vials.', 90, 5499, 'flyers/novanad.jpg', 'Buy 8 sessions, get 2 free', false, [['Per session (Bermonths promo)', 5499, null]]],

        // Botox Procedures
        'botox' => ['injectables', 'Botox Procedures', 'Botox', "Botox by area: forehead, fine lines, crow's feet, glabellar, masseter, traptox, alartox, sweatox, Barbie arms, calves and microtox.", 30, 200, 'botox.jpg', 'Priced per unit or per area', false, [
            ['Forehead', 200, 'per unit'],
            ["Crow's Feet (30 units)", 3000, null],
            ['Fine Lines', 200, 'per unit'],
            ['Sweatox: palms, feet, underarms (100 units)', 10000, null],
            ['Sweatox: palms, feet, underarms (200 units)', 15000, null],
            ['Masseter (40 units)', 4000, null],
            ['Traptox', 200, 'per unit'],
            ['Glabellar (15 units)', 2000, null],
            ['Alartox (30 units)', 3000, null],
            ['Barbie Arms (100 units)', 10000, null],
            ['Barbie Arms (200 units)', 15000, null],
            ['Calves Reduction (100 units)', 10000, null],
            ['Calves Reduction (200 units)', 15000, null],
            ['Microtox', 15000, null],
        ]],
        // Filler Procedures
        'fillers' => ['injectables', 'Filler Procedures', 'Fillers', 'Fillers for the lips and chin, nose and under-eyes, priced per ml.', 45, 10000, 'flyers/lip-fillers.jpg', 'Priced per ml', false, [['Lip / Chin', 10000, 'per ml'], ['Nose', 10000, 'per ml'], ['Under-Eye', 10000, 'per ml']]],
        'hiko-nose-lift' => ['injectables', 'Non-Surgical Nose Lift', 'Hiko Nose Lift', 'A non-surgical nose lift.', 60, 0, 'flyers/hiko-nose.jpg', 'By appointment', false, []],
        // Thread Procedures (non invasive enhancement)
        'thread-lift' => ['injectables', 'Thread Procedures', 'Thread Lift', 'Non-invasive thread enhancement for the jaw and neck, the full face or the neck.', 60, 12999, 'asian-dermatologist-consultation.jpg', 'By area', false, [['Jaw & Neck', 13999, null], ['Full Face', 14999, null], ['Full Face & Neck', 16999, null], ['Neck Lift', 12999, null]]],

        // Non-invasive aesthetic procedures, by appointment only
        'rejuran-healer' => ['skin-boosters', 'Skin Boosters', 'Rejuran Healer', 'A polynucleotide (PN) skin booster for skin repair and hydration.', 45, 0, 'booster.jpg', 'By appointment', false, []],
        'profhilo' => ['skin-boosters', 'Skin Boosters', 'Profhilo', 'A hyaluronic acid skin booster for hydration and skin quality.', 45, 0, 'booster.jpg', 'By appointment', false, []],
        'luhilo' => ['skin-boosters', 'Skin Boosters', 'Luhilo', 'A skin booster for hydration, fine lines, skin tone and texture.', 45, 0, 'flyers/luhilo.jpg', 'By appointment', false, []],
        'luhilo-snow' => ['skin-boosters', 'Skin Boosters', 'Luhilo Snow', 'A hydrating, brightening skin booster for the under-eyes, face, neck, decolletage, hands, elbows and knees.', 45, 0, 'flyers/luhilo-snow.jpg', 'By appointment', false, []],
        'mesanomax' => ['skin-boosters', 'Skin Boosters', 'Mesanomax (PLLA+HA)', 'A skin booster that combines PLLA and hyaluronic acid.', 45, 0, 'booster.jpg', 'By appointment', false, []],
        'collagen-regeneration-plla-pn' => ['skin-boosters', 'Skin Boosters', 'Collagen Regeneration with PLLA and PN', 'Collagen regeneration with PLLA and polynucleotides, also offered for acne scars.', 45, 0, 'booster.jpg', 'By appointment', false, []],
        'nctf-skin-booster' => ['skin-boosters', 'Skin Boosters', 'NCTF Skin Booster', 'Fillmed NCTF 135 HA for deep hydration, elasticity, fine lines, pigmentation and radiance.', 45, 5000, 'flyers/nctf-skin-booster.jpg', '1 vial per session', false, [['1 vial per session', 5000, null]]],
        'laennec-placenta-shot' => ['skin-boosters', 'Anti-Aging / Fountain of Youth Program', 'Laennec Placenta Shot', 'A placenta injection, part of the Fountain of Youth program.', 30, 0, 'wellness.jpg', 'By appointment', false, []],
        'rejuran-s-acne-scar-subcision' => ['skin-boosters', 'Acne Scar Subcision Treatment with PN', 'Acne Scar Subcision with Rejuran S', 'Subcision for acne scars with Rejuran S, a concentrated polynucleotide (PN) skin booster.', 60, 0, 'flyers/rejuran-s.jpg', 'By appointment', false, []],
        'rejuran-i' => ['skin-boosters', 'Panda Eyes Treatment', 'Rejuran I Treatment', 'A polynucleotide treatment for the under-eye area.', 45, 0, 'booster.jpg', 'By appointment', false, []],
        'eyebag-solution' => ['skin-boosters', 'Panda Eyes Treatment', 'Eyebag Solution', 'A non-surgical treatment for under-eye bags.', 45, 0, 'skin-closeup.jpg', 'By appointment', false, []],
        'spicules-biomicroneedling' => ['skin-boosters', 'Melasma Treatment', 'Spicules Biomicroneedling', 'Spicule bio-microneedling for melasma and uneven tone.', 60, 0, 'flyers/aqua-sponge-peel.jpg', 'By appointment', false, []],
        'tranexamic-injection' => ['skin-boosters', 'Melasma Treatment', 'Tranexamic Injection', 'A tranexamic acid injection for melasma.', 30, 0, 'results/melasma.jpg', 'By appointment', false, []],
        'pdrn-injection' => ['skin-boosters', 'Melasma Treatment', 'PDRN Injection', 'A PDRN (polydeoxyribonucleotide) injection for melasma and skin repair.', 30, 0, 'booster.jpg', 'By appointment', false, []],
        'melasma-snow-skin-bright' => ['skin-boosters', 'Melasma Treatment', 'Melasma Snow Skin Bright', 'A brightening treatment for melasma.', 60, 0, 'results/melasma.jpg', 'By appointment', false, []],
        'prp-vampire-facial' => ['skin-boosters', 'PRP Treatments', 'Vampire Facial', 'Platelet-rich plasma (PRP) from your own blood, used to refresh skin texture and tone.', 60, 5000, 'prp.jpg', 'As advised', false, []],
        'prp-facial-fillers' => ['skin-boosters', 'PRP Treatments', 'PRP Facial Fillers', 'Platelet-rich plasma used as a facial filler, also offered for acne scars.', 45, 2500, 'prp.jpg', 'As advised', false, []],
        'prp-stretch-mark-removal' => ['skin-boosters', 'PRP Treatments', 'Stretch Mark Removal', 'PRP treatment for stretch marks, priced per session.', 60, 5000, 'prp.jpg', 'Per session', false, [['Per session', 5000, 'starts at']]],
        'prp-hair-loss' => ['skin-boosters', 'PRP Treatments', 'PRP Hair Loss Treatment', 'PRP for thinning hair and hair re-growth, priced per session.', 60, 6000, 'hair.jpg', 'Per session', false, [['Per session', 6000, 'starts at']]],
        'sclerotherapy' => ['skin-boosters', 'Other Procedures', 'Sclerotherapy', 'An injection treatment for spider veins.', 45, 0, 'skin-closeup.jpg', 'By appointment', false, []],

        // Lifting and tightening
        '9d-hifu' => ['lifting', null, '9D HIFU', 'High-intensity focused ultrasound for skin tightening and body contouring.', 60, 5000, 'asian-dermatologist-consultation.jpg', 'By area', true, [['Face', 5000, null], ['Face & Neck', 7000, null], ['Arms', 6000, null], ['Braline', 6000, null], ['Thighs', 6000, null], ['Stomach', 7000, null]]],
        '12d-hifu' => ['lifting', null, '12D HIFU', 'A non-invasive treatment using high-intensity focused ultrasound to lift, tighten and rejuvenate the skin, for the whole body and face.', 60, 0, 'flyers/12d-hifu.jpg', 'Whole body and face', false, []],
        'thermalift-flx' => ['lifting', null, 'Thermalift FLX', 'Skin tightening for the face, neck, arms, braline and tummy.', 60, 7500, 'technology.jpg', 'By area', false, [['Face', 7500, null], ['Face & Neck', 9000, null], ['Arms', 8000, null], ['Braline', 8000, null], ['Tummy', 8000, null]]],
        'exilift-ultra-360' => ['lifting', null, 'Exilift Ultra 360', 'Skin tightening and body contouring from the eyes to the thighs.', 45, 800, 'rf-body.jpg', 'By area', false, [['Face', 1800, null], ['Face & Neck', 2500, null], ['Arms', 1500, null], ['Braline', 3500, null], ['Thighs', 3000, null], ['Tummy', 3000, null], ['Hands', 1500, null], ['Eyes', 800, null]]],
        'thermage' => ['lifting', null, 'Thermage Skin Tightening', 'Skin tightening that can smoothen and contour for a younger-looking appearance.', 60, 0, 'flyers/thermage.jpg', 'By appointment', false, []],
        'plasma-lift' => ['lifting', null, 'Plasma Lift / Fibroblast', "Plasma fibroblast for the eyelids, mouth, crow's feet and forehead.", 60, 8000, 'peel.jpg', 'By area', false, [['Upper / Lower Eyelid', 8000, null], ['Lower Mouth', 8000, null], ["Crow's Feet", 10000, null], ['Forehead', 12000, null]]],

        // Laser
        'fotona-sp-dynamis' => ['laser', null, 'Fotona SP Dynamis Laser', 'Non-invasive laser lifting, for deep acne scars, resurfacing and active acne.', 60, 5000, 'clinic/cebu-carbon-laser.jpg', 'Per session', true, [['Fractional (Deep Acne Scars)', 5000, 'per session'], ['Resurfacing (Rejuvenation Laser)', 5000, 'per session'], ['Active Acne', 5000, 'per session']]],
        'diode-laser-hair-removal' => ['laser', null, 'Diode Laser Hair Removal', 'Diode laser hair removal for the face, underarms, arms, legs, Brazilian and bikini.', 30, 1000, 'flyers/diode-laser-hair-removal.jpg', 'Per session', false, [['Both Upper & Lower Lip', 1000, 'per session'], ['Underarm', 1000, 'per session'], ['Mustache & Beard', 1000, 'per session'], ['Upper Arm', 1000, 'per session'], ['Lower Arm', 1000, 'per session'], ['Upper Leg', 1300, 'per session'], ['Lower Leg', 1300, 'per session'], ['Brazilian', 1500, 'per session'], ['Bikini', 1500, 'per session']]],
        'warts-removal' => ['laser', null, 'Unlimited Warts Removal', 'Warts removal per area (face, neck, chest, abdomen, upper back, lower back or nape) or for the whole body.', 60, 1200, 'clinic/cebu-facial-closeup.jpg', 'One visit', false, [['Per Area', 1200, null], ['Whole Body', 10000, null]]],
        'vaginal-rejuvenation' => ['laser', null, 'Vaginal Rejuvenation', 'Laser vaginal tightening, a non-surgical procedure that uses laser energy to rejuvenate the vaginal area.', 60, 0, 'flyers/vaginal-rejuvenation.jpg', 'By appointment', false, []],

        // Slimming and body
        'cryolipolysis' => ['slimming', 'Body Contouring', 'Cryolipolysis', 'Fat freezing for the tummy, braline, arms and chin.', 60, 1000, 'rf-body.jpg', 'By area', false, [['Tummy', 2000, null], ['Braline', 2000, null], ['Arms', 2000, null], ['Chin', 1000, null]]],
        'emsculpture' => ['slimming', 'Body Contouring', 'Emsculpture', 'Body contouring for the tummy, thighs, arms and butt.', 30, 1500, 'films/emsculpt.jpg', 'By area', false, [['Tummy', 2000, null], ['Thighs', 1500, null], ['Arms', 1500, null], ['Butt', 2000, null]]],
        'meso-lipo' => ['slimming', 'Fat Dissolving Injections', 'Meso-Lipo', 'Fat-dissolving injections for the double chin, arms, belly area, love handles and bra bulge.', 30, 1500, 'body.jpg', 'Price starts at 1,500 per 1 ml', false, [['Price starts at', 1500, 'per 1 ml']]],
        'lemon-bottle' => ['slimming', 'Fat Dissolving Injections', 'Lemon Bottle', 'Lemon Bottle ampoule solution for face and body. Gluta RepubliQ is an authorized treatment provider.', 30, 0, 'flyers/lemon-bottle.jpg', 'By appointment', false, []],
        'peach-bottle' => ['slimming', 'Fat Dissolving Injections', 'Peach Bottle', 'A fat-dissolving injection and slimming solution for face and body.', 30, 0, 'flyers/peach-bottle.jpg', 'By appointment', false, []],
        'weight-loss-starter-shots' => ['slimming', 'Weight Loss Program', 'Weight Loss Starter Shots', "Starter shots for the weight loss program, after a doctor's assessment.", 30, 0, 'body.jpg', 'By appointment', false, []],
        'weight-loss-3-months-program' => ['slimming', 'Weight Loss Program', '3 Months Weight Loss Program', "The three-month weight loss program, planned after a doctor's assessment.", 30, 0, 'body.jpg', 'By appointment', false, []],

        // SPMU, semi permanent make up
        'skin-camouflage' => ['spmu', 'Semi-Permanent Makeup', 'Skin Camouflage', 'Pigment that blends scars and uneven patches into the surrounding skin.', 180, 10000, 'makeup.jpg', 'As advised', false, [['Skin Camouflage', 10000, 'starts at']]],
        'bb-glow-meso-therapy' => ['spmu', 'Semi-Permanent Makeup', 'BB Glow Meso Therapy', 'Semi-permanent BB glow applied with meso therapy.', 60, 1500, 'facial.jpg', 'As advised', false, []],
        'bb-blush' => ['spmu', 'Semi-Permanent Makeup', 'BB Blush', 'Semi-permanent blush.', 60, 800, 'makeup-2.jpg', 'As advised', false, []],
        'bb-glow-blush-contour' => ['spmu', 'Semi-Permanent Makeup', 'BB Glow with Blush and/or Contour', 'BB glow with semi-permanent blush, contour or both.', 90, 2000, 'makeup-2.jpg', 'As advised', false, []],
        'misty-brows' => ['spmu', 'Semi-Permanent Makeup', 'Misty Brows', 'Semi-permanent powder brows.', 120, 4999, 'results/misty-brows.jpg', 'Retouch available', false, [['Misty Brows', 4999, null], ['Retouch', 2500, null]]],
        'microblading-6d-7d' => ['spmu', 'Semi-Permanent Makeup', '6D/7D Microblading', 'Semi-permanent hair-stroke brows.', 120, 4500, 'makeup.jpg', 'Retouch available', false, [['6D/7D Microblading', 4500, null], ['Retouch', 2250, null]]],
        '6d-fusion-brows' => ['spmu', 'Semi-Permanent Makeup', '6D Fusion Brows', 'Semi-permanent brows that combine hair strokes and shading.', 120, 4500, 'makeup.jpg', 'Retouch available', false, [['6D Fusion Brows', 4500, null], ['Retouch', 2250, null]]],
        'scalp-micropigmentation' => ['spmu', 'Semi-Permanent Makeup', 'Scalp Micropigmentation', 'Pigment applied to the scalp for the look of fuller hair.', 180, 10000, 'hair.jpg', 'As advised', false, []],
        'aqua-lips' => ['spmu', 'Semi-Permanent Makeup', 'Aqua Lips', 'Semi-permanent lip color and definition.', 120, 4500, 'results/aqua-lips.jpg', 'Retouch available', false, [['Aqua Lips', 4500, null], ['Retouch', 2250, null]]],
        'classic-eyeliner' => ['spmu', 'Semi-Permanent Makeup', 'Classic Eyeliner', 'Semi-permanent classic eyeliner.', 90, 2499, 'makeup.jpg', 'Retouch available', false, [['Classic Eyeliner', 2499, null], ['Retouch', 1250, null]]],
        'lash-lift' => ['spmu', 'Lashes', 'Lash Lift', 'A lift and curl for your natural lashes.', 60, 499, 'makeup-2.jpg', 'As advised', false, []],
        'eyelash-extension' => ['spmu', 'Lashes', 'Eyelash Extension', 'Eyelash extensions.', 90, 999, 'makeup-2.jpg', 'As advised', false, [['Eyelash Extension', 999, 'starts at']]],
    ];

    public function run(): void
    {
        $organization = Organization::where('slug', config('clinic.organization', 'glutarepubliq'))->firstOrFail();
        $org = ['organization_id' => $organization->id];

        foreach (['cebu', 'san-pedro', 'sta-rosa', 'bacoor', 'southwoods'] as $slug) {
            $branch = Branch::withoutGlobalScopes()->where($org + ['slug' => $slug])->first();
            // Hours are the clinic's to edit, so only a branch still on the old free-text schedule gets the default.
            $branch?->update([
                // A seeded default (or the old 455px crop) gives way to the clinic's own photo where there is one;
                // a photo chosen in the office is kept.
                'image' => in_array($branch->getRawOriginal('image'), [null, '/media/photos/about-clinic.jpg', '/media/photos/clinic-interior.jpg'], true)
                    ? match ($slug) {
                        'cebu' => '/media/photos/clinic/cebu-storefront.jpg',
                        'southwoods' => '/media/photos/clinic/southwoods-storefront.jpg',
                        default => '/media/photos/clinic-interior.jpg',
                    }
                    : $branch->getRawOriginal('image'),
            ] + ($this->hasSchedule($branch) ? [] : ['hours' => $slug === 'cebu' ? Branch::CEBU_HOURS : Branch::DEFAULT_HOURS]));
        }

        // The menu is the office's once it is loaded: every deploy seeds, so the
        // menu is only written into an empty catalogue, or a price changed in
        // the office would be put back and a deleted service brought back.
        $menuIsEmpty = TreatmentCategory::withoutGlobalScopes()->where($org)->doesntExist();

        $categories = [];
        foreach ($menuIsEmpty ? self::CATEGORIES : [] as $slug => [$name, $description, $image]) {
            $categories[$slug] = TreatmentCategory::withoutGlobalScopes()->create($org + [
                'slug' => $slug, 'name' => $name, 'description' => $description, 'image' => "/media/photos/$image", 'sort' => count($categories),
            ]);
        }

        $sort = 0;
        foreach ($menuIsEmpty ? self::TREATMENTS : [] as $slug => [$category, $subcategory, $name, $summary, $minutes, $price, $image, $sessions, $featured, $list]) {
            Treatment::withoutGlobalScopes()->create($org + [
                'slug' => $slug,
                'treatment_category_id' => $categories[$category]->id,
                'subcategory' => $subcategory,
                'name' => $name,
                'summary' => $summary,
                'description' => "$summary Every treatment starts with a consultation, so the plan and the price are agreed before anything begins.",
                'duration_minutes' => $minutes,
                'price' => $price,
                'promo_price' => null,
                'price_list' => $list ? array_map(fn (array $row) => ['label' => $row[0], 'price' => $row[1], 'unit' => $row[2]], $list) : null,
                'image' => "/media/photos/$image",
                'preparation' => 'Arrive with clean skin where possible. Inform your physician of any current supplements, medicines or recent treatments.',
                'aftercare' => 'Apply broad-spectrum sunscreen SPF 50 daily, keep skin hydrated, and follow your physician-provided care regimen.',
                'contraindications' => 'Not advised during pregnancy or nursing without physician clearance. Your doctor will assess your medical history during assessment.',
                'recommended_sessions' => $sessions,
                'is_featured' => $featured,
                'is_active' => true,
                'sort' => $sort++,
            ]);
        }

        // The clinicians who take appointments, from the clinic's staff roster.
        // San Pedro has nobody on the roster yet, so it cannot be booked online until someone is added.
        $specialists = [
            ['ivy-neri', 'Ivy Neri', 'Registered Nurse / Branch Manager', 'RN', 'Registered nurse and branch manager at our Cebu clinic.', ['cebu']],
            ['janet-culango', 'Janet Culango', 'Medical Aesthetician', null, 'Medical aesthetician at our Cebu clinic.', ['cebu']],
            ['suzette-jacalan', 'Suzette Jacalan', 'Senior Facialist', null, 'Senior facialist at our Cebu clinic.', ['cebu']],
            ['jenrose-rosacena', 'Jenrose Rosacena', 'Junior Facialist', null, 'Facialist at our Cebu clinic.', ['cebu']],
            ['jamaica-dupilas', 'Jamaica Dupilas', 'Junior Facialist', null, 'Facialist at our Cebu clinic.', ['cebu']],
            ['jermaine-ramirez', 'Jermaine Ramirez', 'Registered Nurse', 'RN', 'Registered nurse at our SOMO A Vista Mall clinic in Bacoor.', ['bacoor']],
            ['marinel-bombaes', 'Marinel Bombaes', 'Facialist', null, 'Facialist at our SOMO A Vista Mall clinic in Bacoor.', ['bacoor']],
            ['nenet-canilas', 'Nenet Canilas', 'Facialist', null, 'Facialist at our SOMO A Vista Mall clinic in Bacoor.', ['bacoor']],
            ['mery-joy-legita', 'Mery Joy Legita', 'Registered Nurse', 'RN', 'Registered nurse at our Southwoods Mall clinic, opening January 2027.', ['southwoods']],
            ['tesha-tolentino-emaas', 'Tesha Tolentino Emaas', 'Senior Facialist', null, 'Senior facialist at our Southwoods Mall clinic, opening January 2027.', ['southwoods']],
        ];

        $branches = Branch::withoutGlobalScopes()->where($org)->pluck('id', 'slug');
        foreach ($specialists as $i => [$slug, $name, $title, $credentials, $bio, $at]) {
            $specialist = Specialist::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'name' => $name, 'title' => $title, 'credentials' => $credentials, 'bio' => $bio,
                'photo' => null, 'focus' => [], 'sort' => $i, 'is_active' => true,
            ]);
            $specialist->branches()->sync($branches->only($at)->values());
        }
        Specialist::withoutGlobalScopes()->where($org)->whereNotIn('slug', array_column($specialists, 0))->update(['is_active' => false]);

        $treatments = Treatment::withoutGlobalScopes()->where($org)->pluck('id', 'slug');
        Testimonial::withoutGlobalScopes()->where($org)->delete();
        // As published on glutarepubliq.com.
        foreach ([
            ['Sheena Aquino', 'IPL underarm hair removal', 'I have now flawless and smooth underarms because of their IPL treatment. Very accommodating and affordable services.', 'ipl-hair-removal'],
            ['Mae Lopez', 'Gluta Drip client', 'Gluta RepubliQ is my go-to clinic for my dose of Gluta Drips. Awesome services always!', 'gluta-drip-bronze'],
            ['Christina Reyes', 'Client', "I couldn't be happier with the results. I have my skin and my confidence back! Everyone in Gluta RepubliQ has been so efficient and friendly, making me feel very comfortable.", null],
        ] as [$author, $meta, $quote, $treatment]) {
            Testimonial::withoutGlobalScopes()->create($org + ['author_name' => $author, 'author_meta' => $meta, 'quote' => $quote, 'treatment_id' => $treatments[$treatment] ?? null]);
        }

        Faq::withoutGlobalScopes()->where($org)->delete();
        foreach ([
            ['Are the treatments safe?', 'Yes, all our treatments and glutathione formulations are performed using FDA-approved technology under the direct supervision of licensed physicians and registered nurses.'],
            ['How many sessions are required?', 'Visible radiance is often noticed after 1–2 sessions. For sustained brightening and collagen remodeling, a customized series of 4–8 sessions is typically recommended.'],
            ['Is there any downtime?', 'Most treatments, including our gluta drips and Hydrafacial, have zero downtime. Gentle laser sessions may have mild rosy flushing for 1–2 hours.'],
            ['Do you offer customized treatment plans?', 'Every client begins with a thorough skin assessment. Your physician customizes the protocol, active concentrations, and schedule for your unique skin profile.'],
            ['Which branches can I visit?', 'We have three clinics: San Pedro, Laguna (Esperanza Bldg., Pacita Ave.), SOMO A Vista Mall in Bacoor, Cavite, and Robinsons Galleria in Cebu City. A fourth clinic opens at Southwoods Mall, Biñan, Laguna in January 2027.'],
            ['How can I pay?', 'Cash, Visa, Mastercard, GCash and Maya are accepted at every branch.'],
            ['Are prices fixed?', 'Prices on this site are our current rates and are subject to change without prior notice. Your plan and its price are confirmed at your consultation, before any treatment.'],
        ] as $i => [$question, $answer]) {
            Faq::withoutGlobalScopes()->create($org + ['question' => $question, 'answer' => $answer, 'sort' => $i]);
        }
    }

    private function hasSchedule(Branch $branch): bool
    {
        $raw = json_decode($branch->getRawOriginal('hours') ?? 'null', true);

        return is_array($raw) && array_is_list($raw) && count($raw) === 7;
    }
}
