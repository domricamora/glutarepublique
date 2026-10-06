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
        'gluta' => ['Glutathione Therapies', 'Medical-grade antioxidant drips and skin brightening infusions administered by licensed nurses.', 'asian-gluta-drip.jpg'],
        'facial' => ['Facial Treatments', 'Deep cleansing, resurfacing and hydration for luminous glass skin.', 'asian-hydraglow-facial.jpg'],
        'laser' => ['Laser & Light Therapy', 'Advanced laser skin rejuvenation, pigmentation and scar revision.', 'asian-laser-treatment.jpg'],
        'anti-aging' => ['Anti-Aging & Firming', 'Physician-led treatments for fine lines, firmness, and collagen renewal.', 'asian-dermatologist-consultation.jpg'],
        'body' => ['Body Contouring', 'Non-invasive shaping, tightening and skin smoothing.', 'asian-gluta-drip.jpg'],
    ];

    /** slug => [category, name, summary, minutes, price, promo, image, sessions, featured] */
    public const TREATMENTS = [
        'hydra-facial' => ['facial', 'HydraGlow Deep Cleansing Facial', 'Medical hydro-dermabrasion, vortex extractions, and serum infusion in one session.', 60, 2500, 1800, 'asian-hydraglow-facial.jpg', 'Monthly', true],
        'glutathione-radiant-drip' => ['gluta', 'Glutathione Radiant Drip', 'High-dose master antioxidant infusion with Vitamin C and collagen for luminous, all-over glow.', 45, 2500, 1999, 'asian-gluta-drip.jpg', '1 session weekly', true],
        'ultimate-glow-drip' => ['gluta', 'Ultimate Glow Drip Cocktail', 'Maximum potency brightening drip formulated for deep hydration and radiance.', 60, 3800, 2999, 'asian-gluta-drip.jpg', '4 to 8 sessions', true],
        'hydraglow-facial' => ['facial', 'HydraGlow Deep Cleansing Facial', 'Medical hydro-dermabrasion, vortex extractions, and serum infusion in one session.', 60, 2500, 1800, 'asian-hydraglow-facial.jpg', 'Monthly', true],
        'laser-skin-rejuvenation' => ['laser', 'Laser Skin Rejuvenation', 'Non-ablative laser targeting texture, enlarged pores, and uneven skin tone.', 45, 4500, 3500, 'asian-laser-treatment.jpg', '3 to 5 sessions', true],
        'pigmentation-melasma-laser' => ['laser', 'Pigmentation & Melasma Treatment', 'Targeted wavelength precision to break down dark patches, sun spots, and melasma.', 45, 3900, 3200, 'mockup-ba2.jpg', '4 to 6 sessions', true],
        'acne-scar-treatment' => ['laser', 'Acne & Acne Scar Revision', 'Physician protocol combining clinical peel and laser passes to smooth acne scarring.', 60, 3500, null, 'mockup-ba1.jpg', '4 to 6 sessions', true],
        'hifu-face-lift' => ['anti-aging', 'HIFU Non-Surgical Face Lift', 'High-Intensity Focused Ultrasound targeting deep SMAS layer for natural jawline contouring.', 60, 7500, 5999, 'asian-dermatologist-consultation.jpg', '1 to 2 sessions yearly', true],
        'carbon-laser-peel' => ['laser', 'Hollywood Carbon Laser Peel', 'Liquid carbon mask combined with Q-switched laser for refreshed, porcelain skin.', 45, 3200, 2500, 'asian-laser-treatment.jpg', '3 to 4 sessions', false],
        // Kept for deep links from the home signature cards; resolves to the same service.
        'hydraglow-facial' => ['facial', 'HydraGlow Deep Cleansing Facial', 'Medical hydro-dermabrasion, vortex extractions, and serum infusion in one session.', 60, 2500, 1800, 'asian-hydraglow-facial.jpg', 'Monthly', false],
        'laser-skin-rejuvenation' => ['laser', 'Laser Skin Rejuvenation', 'Non-ablative laser targeting texture, enlarged pores, and uneven skin tone.', 45, 4500, 3500, 'asian-laser-treatment.jpg', '3 to 5 sessions', false],
        'pigmentation-treatment' => ['laser', 'Pigmentation & Melasma Treatment', 'Targeted wavelength precision to break down dark patches, sun spots, and melasma.', 45, 3900, 3200, 'mockup-ba2.jpg', '4 to 6 sessions', false],
        'acne-scar-treatment-duplicate' => ['laser', 'Acne & Acne Scar Revision', 'Physician protocol combining clinical peel and laser passes to smooth acne scarring.', 60, 3500, null, 'mockup-ba1.jpg', '4 to 6 sessions', false],
    ];

    public function run(): void
    {
        $organization = Organization::where('slug', config('clinic.organization', 'glutarepubliq'))->firstOrFail();
        $org = ['organization_id' => $organization->id];

        $hours = ['Mon to Sat' => '10:00 to 20:00', 'Sunday' => '11:00 to 18:00'];
        foreach (['cebu', 'san-pedro', 'sta-rosa', 'bacoor'] as $slug) {
            Branch::withoutGlobalScopes()->where($org + ['slug' => $slug])->first()?->update([
                'image' => '/media/photos/about-clinic.jpg',
                'hours' => $hours,
            ]);
        }

        $categories = [];
        foreach (self::CATEGORIES as $slug => [$name, $description, $image]) {
            $categories[$slug] = TreatmentCategory::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'name' => $name, 'description' => $description, 'image' => "/media/photos/$image", 'sort' => count($categories),
            ]);
        }

        $sort = 0;
        foreach (self::TREATMENTS as $slug => [$category, $name, $summary, $minutes, $price, $promo, $image, $sessions, $featured]) {
            Treatment::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'treatment_category_id' => $categories[$category]->id,
                'name' => $name,
                'summary' => $summary,
                'description' => "$summary Every treatment is preceded by a personalized consultation to ensure safe, tailored, and optimal results.",
                'duration_minutes' => $minutes,
                'price' => $price,
                'promo_price' => $promo,
                'image' => "/media/photos/$image",
                'preparation' => 'Arrive with clean skin where possible. Inform your physician of any current supplements, medicines or recent treatments.',
                'aftercare' => 'Apply broad-spectrum sunscreen SPF 50 daily, keep skin hydrated, and follow your physician-provided care regimen.',
                'contraindications' => 'Not advised during pregnancy or nursing without physician clearance. Your doctor will assess your medical history during assessment.',
                'recommended_sessions' => $sessions,
                'is_featured' => $featured,
                'sort' => $sort++,
            ]);
        }

        $specialists = [
            ['dr-camille-rivera', 'Dr. Camille Rivera', 'Medical Director, Aesthetic Dermatology', 'MD, Aesthetic & Laser Specialist', 'Dr. Rivera specializes in advanced laser aesthetics, pigmentation clearance, and medical-grade glutathione protocols with over 10 years of clinical practice.', 'asian-dermatologist-consultation.jpg', ['Laser rejuvenation', 'Glutathione protocols', 'Melasma treatment'], ['cebu', 'san-pedro']],
            ['dr-adrian-santos', 'Dr. Adrian Santos', 'Aesthetic Physician', 'MD, Aesthetic Medicine & Trichology', 'Dr. Santos focuses on non-invasive facial sculpting, HIFU, and anti-aging therapies with tailored protocols.', 'asian-doctor-male.jpg', ['HIFU lifting', 'Facial contouring', 'Skin tightening'], ['cebu', 'bacoor', 'san-pedro']],
            ['dr-rhea-kapoor', 'Dr. Rhea Kapoor', 'Cosmetic Dermatologist', 'MD Dermatology', 'Dr. Kapoor brings 8+ years of dedicated expertise in delicate acne scar treatments, dermal peels, and glass-skin facial rejuvenation.', 'doctor-1.jpg', ['HydraGlow', 'Acne scar revision', 'Skin rejuvenation'], ['sta-rosa', 'cebu']],
        ];

        $branches = Branch::withoutGlobalScopes()->where($org)->pluck('id', 'slug');
        foreach ($specialists as $i => [$slug, $name, $title, $credentials, $bio, $photo, $focus, $at]) {
            $specialist = Specialist::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'name' => $name, 'title' => $title, 'credentials' => $credentials, 'bio' => $bio,
                'photo' => "/media/photos/$photo", 'focus' => $focus, 'sort' => $i,
            ]);
            $specialist->branches()->sync($branches->only($at)->values());
        }

        $treatments = Treatment::withoutGlobalScopes()->where($org)->pluck('id', 'slug');
        Testimonial::withoutGlobalScopes()->where($org)->delete();
        foreach ([
            ['Maria Santos', 'Cebu, Glutathione Radiant Drip', 'The difference in my skin radiance after 3 sessions was incredible. Very professional staff and doctors!', 'glutathione-radiant-drip'],
            ['Kristine Joy', 'Robinsons Galleria Cebu, HydraGlow', 'Zero pain and my skin literally had that glass skin glow right after walking out of the clinic.', 'hydraglow-facial'],
            ['Janelle Ramos', 'San Pedro, Pigmentation Treatment', 'My melasma patches started fading noticeably after the customized laser and drip package. Highly recommended!', 'pigmentation-melasma-laser'],
            ['Aileen Cruz', 'Bacoor, Laser Rejuvenation', 'Super clean clinic, kind doctors, and the prices are very accessible without compromising medical quality.', 'laser-skin-rejuvenation'],
        ] as [$author, $meta, $quote, $treatment]) {
            if (isset($treatments[$treatment])) {
                Testimonial::withoutGlobalScopes()->create($org + ['author_name' => $author, 'author_meta' => $meta, 'quote' => $quote, 'treatment_id' => $treatments[$treatment]]);
            }
        }

        Faq::withoutGlobalScopes()->where($org)->delete();
        foreach ([
            ['Are the treatments safe?', 'Yes, all our treatments and glutathione formulations are performed using FDA-approved technology under the direct supervision of licensed physicians and registered nurses.'],
            ['How many sessions are required?', 'Visible radiance is often noticed after 1–2 sessions. For sustained brightening and collagen remodeling, a customized series of 4–8 sessions is typically recommended.'],
            ['Is there any downtime?', 'Most treatments, including our signature glutathione drips and HydraGlow facials, have zero downtime. Gentle laser sessions may have mild rosy flushing for 1–2 hours.'],
            ['Do you offer customized treatment plans?', 'Every client begins with a thorough skin assessment. Your physician customizes the protocol, active concentrations, and schedule for your unique skin profile.'],
            ['Which branches can I visit?', 'We have 4 branches across Cebu City (Robinsons Galleria Cebu II), San Pedro Laguna, Sta. Rosa Laguna, and Bacoor Cavite.'],
        ] as $i => [$question, $answer]) {
            Faq::withoutGlobalScopes()->create($org + ['question' => $question, 'answer' => $answer, 'sort' => $i]);
        }
    }
}
