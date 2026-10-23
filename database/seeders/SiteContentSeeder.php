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
        'gluta' => ['Gluta and IV Drips', 'Gluta push and drip packages infused with vitamins, minerals and antioxidants, with add-on boosters.', 'clinic/cebu-drip-lounge.jpg'],
        'facial' => ['Facials', 'From the signature cleansing facial to Hydrafacial, collagen and PRP facials.', 'asian-hydraglow-facial.jpg'],
        'anti-aging' => ['Lifting and Tightening', 'Non-surgical HIFU, radiofrequency and plasma treatments for firmer skin.', 'asian-dermatologist-consultation.jpg'],
        'laser' => ['Laser and Skin Clinic', 'Fotona and Pico laser, peels, and removal of warts, keloids and skin tags.', 'clinic/cebu-carbon-peel.jpg'],
        'injectables' => ['Botox, Fillers and Threads', 'Doctor-administered botox, dermal fillers and thread lifts.', 'fillers.jpg'],
        'hair-removal' => ['Hair Removal', 'Diode laser and IPL SHR permanent hair removal for men and women.', 'clinic/cebu-laser-hair-removal.jpg'],
        'body' => ['Slimming and Body', 'Emsculpt, cryolipolysis, meso lipo, cavitation and body whitening.', 'rf-body.jpg'],
        'spmu' => ['Brows, Lips and Lashes', 'Semi-permanent makeup: misty brows, microblading, Aqua Lips, eyeliner and lashes.', 'makeup.jpg'],
    ];

    /**
     * The clinic's published menu (glutarepubliq.com, 2026-10-21). Prices are
     * the clinic's own and "subject to change without prior notice"; the list
     * price is the lowest line of the price list, shown as "from".
     *
     * slug => [category, name, summary, minutes, price, image, sessions, featured, price list [label, price, unit]]
     */
    public const TREATMENTS = [
        'hydra-facial' => ['facial', 'Hydrafacial', 'Cleansing, gentle exfoliation, extraction and serum infusion in one visit, with no downtime.', 60, 1500, 'asian-hydraglow-facial.jpg', 'Monthly', true, []],
        'signature-facial' => ['facial', 'Signature Facial Cleansing', 'A cleansing and nourishing facial for congested, dull or dry skin.', 45, 400, 'clinic/cebu-facial.jpg', 'Monthly', false, [['Signature Facial Cleansing', 400, null], ["Gentleman's Facial", 750, null]]],
        'collagen-infusion-facial' => ['facial', 'Collagen Infusion Facial', 'An advanced facial that infuses collagen for plumper, hydrated looking skin.', 60, 2500, 'hydrafacial.jpg', 'Monthly', false, [['Collagen Infusion Facial', 2500, null], ['Black Doll Facial', 2500, null]]],
        'prp-vampire-facial' => ['facial', 'PRP Vampire Facial', 'Platelet-rich plasma from your own blood, used to refresh skin texture and tone.', 60, 2500, 'prp.jpg', '3 sessions, a month apart', false, [['PRP facial fillers', 2500, null], ['PRP Vampire Facial', 5000, null]]],
        'exosome-facial' => ['facial', 'Exosome Facial', 'Exosome-based facial for tired under-eyes and dull skin.', 60, 8000, 'booster.jpg', 'As advised', false, [['Exosome Facial', 8000, null], ['Premium Exosome Facial', 15000, null]]],

        'gluta-drip-bronze' => ['gluta', 'Bronze Gluta Drip', 'Gluta drip infused with vitamins and antioxidants to brighten and even skin tone.', 45, 900, 'clinic/cebu-drip-lounge.jpg', '5+1 or 10+3 packages', true, [['Per session', 900, null], ['5+1 package', 3699, null], ['10+3 package', 7299, null]]],
        'gluta-drip-gold' => ['gluta', 'Gold Gluta Drip', 'A higher-dose gluta drip for brightening, detox and a healthy glow.', 45, 1800, 'clinic/cebu-iv-drip.jpg', '5+1 or 10+3 packages', false, [['Per session', 1800, null], ['5+1 package', 7299, null], ['10+3 package', 14499, null]]],
        'diamond-drip' => ['gluta', 'Diamond Drip', 'Premium gluta drip with added antioxidants for skin radiance.', 60, 2500, 'clinic/cebu-lounge.jpg', '5+1 or 10+3 packages', false, [['Per session', 2500, null], ['5+1 package', 9999, null], ['10+3 package', 19999, null]]],
        'platinum-drip' => ['gluta', 'Platinum Drip', "The clinic's top-tier drip for brightening, detox and immunity support.", 60, 3000, 'wellness.jpg', '5+1 or 10+3 packages', false, [['Per session', 3000, null], ['5+1 package', 14000, null], ['10+3 package', 26000, null]]],
        'drip-add-ons' => ['gluta', 'Drip Add-ons', 'Boosters added to any gluta drip.', 15, 100, 'iv.jpg', 'With any drip', false, [['Vitamin C infusion', 100, null], ['Athletic / B-Complex', 200, null], ['Collagen infusion', 500, null], ['Placenta infusion', 500, null], ['Slimming', 500, null], ['Stemcell', 850, null], ['NAD+', 1000, null]]],
        'nad-iv-therapy' => ['gluta', 'NovaNAD+ IV Therapy', 'NAD+ IV drip for cellular repair and energy, given by licensed nurses.', 90, 5499, 'iv.jpg', 'As advised', false, [['100 mg', 5499, null], ['250 mg', 15999, null], ['500 mg', 24990, null]]],

        'hifu-face-lift' => ['anti-aging', '9D HIFU', 'High-intensity focused ultrasound that lifts and tones the face and body, with no surgery.', 60, 5000, 'asian-dermatologist-consultation.jpg', '1 to 2 sessions a year', true, [['Face', 5000, null], ['Face and neck', 7000, null], ['Arms', 6000, null], ['Bra line', 6000, null], ['Thighs', 6000, null], ['Stomach', 7000, null]]],
        'thermalift-flx' => ['anti-aging', 'Thermalift FLX', 'Radiofrequency skin tightening and lifting for the face and body.', 60, 7500, 'technology.jpg', 'As advised', false, [['Face', 7500, null], ['Face and neck', 9000, null], ['Arms', 8000, null], ['Bra line', 8000, null], ['Tummy', 8000, null]]],
        'exilift-ultra-360' => ['anti-aging', 'Exilift Ultra 360', 'Radiofrequency and ultrasound for firmer skin and contouring, with no downtime.', 45, 800, 'rf-body.jpg', 'A series of sessions', false, [['Eyes', 800, null], ['Face', 1800, null], ['Face and neck', 2500, null], ['Arms', 1500, null], ['Hands', 1500, null], ['Thighs', 3000, null], ['Tummy', 3000, null], ['Bra line', 3500, null]]],
        'plasma-lift' => ['anti-aging', 'Plasma Lift / Fibroblast', 'Plasma energy that tightens skin and softens fine lines, without surgery.', 60, 8000, 'peel.jpg', 'As advised', false, [['Upper or lower eyelid', 8000, null], ['Lower mouth', 8000, null], ["Crow's feet", 10000, null], ['Forehead', 12000, null]]],
        'laennec-placenta' => ['anti-aging', 'Laennec Placenta Shot', 'Japanese placenta injection for skin renewal and vitality.', 30, 1800, 'booster.jpg', '5-session package', false, [['Per shot', 1800, null], ['5 sessions', 8499, null]]],

        'fotona-sp-dynamis' => ['laser', 'Fotona SP Dynamis Laser', 'Laser for deep acne scars, active acne and resurfacing, with minimal downtime.', 60, 5000, 'asian-laser-treatment.jpg', '3 to 5 sessions', true, [['Fractional (deep acne scars)', 5000, 'per session'], ['Resurfacing (rejuvenation)', 5000, 'per session'], ['Active acne', 5000, 'per session']]],
        'pico-laser' => ['laser', 'Pico Laser', 'Short laser pulses that fade dark spots, acne marks, uneven tone and tattoos.', 45, 1000, 'clinic/cebu-carbon-laser.jpg', 'A series of sessions', false, [['Hyperpigmentation', 1000, 'from, per session'], ['Pore reduction', 1000, 'from, per session'], ['Panda eye laser', 1000, 'from, per session'], ['Underarm, bikini, elbow or knee whitening', 1000, 'from, per session'], ['Tattoo removal', 1000, 'from, per session']]],
        'skin-clinic' => ['laser', 'Keloid, Skin Tag and TCA Peel', 'Non-surgical treatment of keloids, skin tags, milia and discoloration.', 45, 1000, 'clinic/cebu-facial-closeup.jpg', 'As advised', false, [['TCA peel', 1000, 'from, per session'], ['Skin tag removal', 1000, 'from, per session'], ['Milia or syringoma removal', 1000, 'from, per session'], ['Keloid removal', 1500, 'from, per session']]],
        'warts-removal' => ['laser', 'Unlimited Warts Removal', 'Removal of warts per area or across the whole body, for one flat rate.', 60, 1200, 'consultation.jpg', 'One visit', false, [['Per area', 1200, null], ['Whole body', 10000, null]]],

        'botox' => ['injectables', 'Botox', 'Relaxes the muscles behind lines and contours the jaw, arms and calves.', 30, 2000, 'botox.jpg', 'Every 4 to 6 months', false, [['Glabellar lines (15 units)', 2000, null], ["Crow's feet (30 units)", 3000, null], ['Alartox (30 units)', 3000, null], ['Masseter (40 units)', 4000, null], ['Forehead, fine lines, traptox', 200, 'per unit'], ['Sweatox, Barbie arms or calves (100 units)', 10000, null], ['Sweatox, Barbie arms or calves (200 units)', 15000, null], ['Microtox', 15000, null]]],
        'fillers' => ['injectables', 'Dermal Fillers', 'Hyaluronic fillers to restore volume and shape the lips, nose, chin and under-eyes.', 45, 10000, 'fillers.jpg', 'Every 9 to 12 months', false, [['Fillers', 10000, 'per ml'], ['Lips or chin', 10000, 'per ml'], ['Nose', 10000, null]]],
        'hiko-nose-lift' => ['injectables', 'Hiko Nose Lift', 'Non-surgical nose lift with 10 threads, Alartox and filler.', 60, 9999, 'fillers.jpg', 'One visit', false, []],
        'thread-lift' => ['injectables', 'Thread Lift', 'Dissolvable threads that lift the face, jawline and neck.', 60, 12999, 'asian-dermatologist-consultation.jpg', 'One visit', false, [['Neck lift', 12999, null], ['Jaw and neck', 13999, null], ['Full face', 14999, null], ['Full face and neck', 16999, null]]],

        'diode-laser-hair-removal' => ['hair-removal', 'Diode Laser Hair Removal', 'Permanent hair reduction for men and women, with little discomfort and no downtime.', 30, 4499, 'clinic/cebu-laser-hair-removal.jpg', '6 sessions', false, [['Upper leg', 4499, '6 sessions'], ['Upper and lower lip', 4999, '6 sessions'], ['Underarms', 4999, '6 sessions'], ['Mustache and beard', 4999, '6 sessions'], ['Arms', 4999, '6 sessions'], ['Brazilian and bikini', 6299, '6 sessions'], ['Lower leg', 6499, '6 sessions']]],
        'ipl-hair-removal' => ['hair-removal', 'IPL OPT + SHR Hair Removal', 'Intense pulsed light and super hair removal for smooth, irritation-free skin.', 30, 499, 'hair.jpg', 'Buy 10, get 1 free', false, [['Upper and lower lip', 499, 'per session'], ['Underarms', 499, 'per session'], ['Mustache and beard', 499, 'per session'], ['Arms', 699, 'per session'], ['Lower leg', 799, 'per session'], ['Upper leg', 799, 'per session'], ['Brazilian and bikini', 1000, 'per session']]],

        'emsculpt' => ['body', 'Emsculpt', 'Electromagnetic energy that tones and strengthens the tummy, arms, thighs and butt.', 30, 1500, 'body.jpg', 'A series of sessions', false, [['Thighs', 1500, null], ['Arms', 1500, null], ['Tummy', 2000, null], ['Butt', 2000, null]]],
        'cryolipolysis' => ['body', 'Cryolipolysis', 'Fat-freezing for the tummy, bra line, arms and chin.', 60, 1000, 'rf-body.jpg', 'A series of sessions', false, [['Chin', 1000, null], ['Tummy', 2000, null], ['Bra line', 2000, null], ['Arms', 2000, null]]],
        'meso-lipo' => ['body', 'Meso Lipo', 'Fat-dissolving micro-injections for the double chin, arms, belly and love handles.', 30, 1500, 'body.jpg', 'A series of sessions', false, [['Double chin', 1500, 'from, per ml'], ['Arms', 5000, 'per ml']]],
        'slimming' => ['body', 'Slimming and Body Contouring', 'Ultrasound cavitation, radiofrequency and vacuum RF for body shaping.', 45, 399, 'rf-body.jpg', '5+1 or 10+3 packages', false, [['Ultrasound cavitation', 399, 'per session'], ['Radio frequency', 399, 'per session'], ['Vacuum RF', 499, 'per session'], ['Slimming injection', 500, 'per session']]],
        'body-whitening' => ['body', 'Whitening Body Bleach', 'Cleanses and lightens the underarms, neck, thighs, elbows and knees.', 45, 200, 'wellness.jpg', 'A series of sessions', false, [['Hands', 200, null], ['Feet', 250, null], ['Stomach', 350, null], ['Feet and hands', 350, null], ['Underarms', 400, null], ['Inner thighs', 400, null], ['Neck and nape', 400, null], ['Full hands', 450, null], ['Full legs', 700, null], ['Whole body', 1500, null]]],

        'misty-brows' => ['spmu', 'Misty Brows and Microblading', 'Semi-permanent powder or hair-stroke brows that hold their shape for months.', 120, 4500, 'makeup.jpg', 'Retouch after 4 to 6 weeks', false, [['6D/7D microblading', 4500, null], ['6D fusion brows', 4500, null], ['Misty brows', 4999, null], ['Microblading or fusion retouch', 2250, null], ['Misty brows retouch', 2500, null]]],
        'aqua-lips' => ['spmu', 'Aqua Lips', 'Semi-permanent lip color and definition.', 120, 4500, 'makeup-2.jpg', 'Retouch after 4 to 6 weeks', false, [['Aqua Lips', 4500, null], ['Retouch', 2250, null]]],
        'bb-glow' => ['spmu', 'BB Glow and Blush', 'Semi-permanent BB glow with meso therapy, blush or contour.', 60, 800, 'facial.jpg', 'A series of sessions', false, [['BB blush', 800, null], ['BB glow meso therapy', 1500, null], ['BB glow with blush or contour', 2000, null]]],
        'eyeliner-lashes' => ['spmu', 'Eyeliner and Lashes', 'Classic semi-permanent eyeliner, lash lift and extensions.', 60, 499, 'makeup.jpg', 'As advised', false, [['Lash lift', 499, null], ['Eyelash extension', 999, 'from'], ['Eyeliner retouch', 1250, null], ['Classic eyeliner', 2499, null]]],
        'scalp-micropigmentation' => ['spmu', 'Scalp Micropigmentation', 'Pigment applied to the scalp to give the look of fuller hair.', 180, 10000, 'hair.jpg', 'As advised', false, []],
    ];

    /** The first demo menu, replaced by the clinic's real one. Kept as inactive rows because bookings and receipts may name them. */
    public const RETIRED = [
        'glutathione-radiant-drip', 'ultimate-glow-drip', 'hydraglow-facial', 'laser-skin-rejuvenation', 'pigmentation-melasma-laser',
        'pigmentation-treatment', 'acne-scar-treatment', 'acne-scar-treatment-duplicate', 'carbon-laser-peel',
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

        $categories = [];
        foreach (self::CATEGORIES as $slug => [$name, $description, $image]) {
            $categories[$slug] = TreatmentCategory::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'name' => $name, 'description' => $description, 'image' => "/media/photos/$image", 'sort' => count($categories),
            ]);
        }

        $sort = 0;
        foreach (self::TREATMENTS as $slug => [$category, $name, $summary, $minutes, $price, $image, $sessions, $featured, $list]) {
            Treatment::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'treatment_category_id' => $categories[$category]->id,
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

        Treatment::withoutGlobalScopes()->where($org)->whereIn('slug', self::RETIRED)->update(['is_active' => false, 'is_featured' => false]);

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
            ['Is there any downtime?', 'Most treatments, including our signature glutathione drips and HydraGlow facials, have zero downtime. Gentle laser sessions may have mild rosy flushing for 1–2 hours.'],
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
