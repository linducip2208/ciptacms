<?php

namespace Database\Seeders;

use App\Core\Services\CompanyProfileService;
use App\Models\Cp\Career;
use App\Models\Cp\Client;
use App\Models\Cp\Faq;
use App\Models\Cp\GalleryAlbum;
use App\Models\Cp\GalleryImage;
use App\Models\Cp\Portfolio;
use App\Models\Cp\Product;
use App\Models\Cp\Service;
use App\Models\Cp\TeamMember;
use App\Models\Cp\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAbout();
        $this->seedServices();
        $this->seedProducts();
        $this->seedPortfolio();
        $this->seedTeam();
        $this->seedTestimonials();
        $this->seedClients();
        $this->seedFaqs();
        $this->seedGallery();
        $this->seedCareers();
    }

    protected function seedAbout(): void
    {
        $company = app(CompanyProfileService::class);
        $company->saveAbout([
            'about.description' => 'Lindu Studio is a digital product studio helping companies build strong online brands. We combine strategy, design and engineering to ship websites, applications and content platforms that actually move the numbers.',
            'about.history' => "Founded in 2015, we started as a three-person web studio.\n\nToday we work with startups and established companies across Southeast Asia on brand sites, e-commerce, portals and internal tools.\n\nOur approach has not changed: understand the business first, then design the simplest thing that solves it well.",
            'about.vision' => 'To be the most trusted digital partner for companies in the region — the studio businesses call first when the project matters.',
            'about.mission' => 'We build digital products that are fast, accessible and easy for our clients to run themselves, long after we hand them over.',
            'about.values' => [
                'Clarity over cleverness — the simplest solution that works wins.',
                'Ship early, improve often.',
                'Own the outcome, not just the deliverable.',
                'Accessible and fast by default, not as an afterthought.',
            ],
        ]);

        $company->saveContact([
            'contact.address' => "Lindu Studio\nJl. Merdeka No. 17, Lt. 4\nJakarta Selatan 12190, Indonesia",
            'contact.phone' => '+62 21 555 0199',
            'contact.whatsapp' => '+6281234567890',
            'contact.email' => 'hello@example.com',
            'contact.business_hours' => "Monday – Friday: 09:00 – 18:00\nSaturday: 09:00 – 13:00\nSunday: Closed",
            'contact.social' => [
                'linkedin' => 'https://linkedin.com/company/example',
                'instagram' => 'https://instagram.com/example',
                'facebook' => 'https://facebook.com/example',
                'youtube' => 'https://youtube.com/@example',
            ],
        ]);
    }

    protected function seedServices(): void
    {
        if (Service::count() > 0) {
            return;
        }

        $rows = [
            [
                'title' => 'Website Development',
                'icon' => '🌐',
                'excerpt' => 'Fast, accessible marketing sites and web platforms built on the Lindu CMS engine.',
                'description' => '<p>We design and build websites that load fast, rank well and are simple for your team to update. Everything ships on the Lindu CMS core, so you get page builder, blog, SEO and media management out of the box.</p>',
                'features' => ['Responsive design for every breakpoint', 'Visual page builder — no developer needed', 'SEO-ready structure and metadata', 'Blog and news module', 'Easy content updates'],
                'cta_label' => 'Start your website',
                'cta_url' => '/contact',
            ],
            [
                'title' => 'E-Commerce Solutions',
                'icon' => '🛒',
                'excerpt' => 'Online stores with catalogue, checkout and payment gateway integration.',
                'description' => '<p>From a simple catalogue to a full storefront, we build online shops that are fast on mobile and safe for payments. Integrates with Xendit, iPaymu, Tripay and Stripe.</p>',
                'features' => ['Product catalogue with variants', 'Secure multi-gateway checkout', 'Order and inventory management', 'Customer accounts and history'],
                'cta_label' => 'Talk to sales',
                'cta_url' => '/contact',
            ],
            [
                'title' => 'Brand & Identity',
                'icon' => '🎨',
                'excerpt' => 'Logo, colour system, typography and brand guidelines your team can actually use.',
                'description' => '<p>A good brand is consistent everywhere. We build the identity system and the practical guidelines that keep your team on-brand across web, print and social.</p>',
                'features' => ['Logo design and variants', 'Colour and type system', 'Brand guidelines document', 'Social media templates'],
                'cta_label' => 'Request a proposal',
                'cta_url' => '/contact',
            ],
            [
                'title' => 'SEO & Content',
                'icon' => '📈',
                'excerpt' => 'Technical SEO, content strategy and analytics setup so you can measure what works.',
                'description' => '<p>We set up technical SEO properly — clean URLs, structured data, fast pages and a working sitemap — then help you plan content that brings the right people in.</p>',
                'features' => ['Technical SEO audit', 'Keyword research and mapping', 'Structured data and schema', 'Analytics and reporting'],
                'cta_label' => 'Get an audit',
                'cta_url' => '/contact',
            ],
            [
                'title' => 'Custom Software',
                'icon' => '⚙️',
                'excerpt' => 'Internal tools, dashboards and integrations built around how your team already works.',
                'description' => '<p>Sometimes off-the-shelf is not enough. We build internal tools, dashboards and integrations that fit your existing process instead of forcing you to change it.</p>',
                'features' => ['Process analysis and design', 'Admin dashboards and reporting', 'Third-party integrations', 'Maintenance and support plans'],
                'cta_label' => 'Discuss a project',
                'cta_url' => '/contact',
            ],
            [
                'title' => 'Maintenance & Support',
                'icon' => '🛡️',
                'excerpt' => 'Hosting, backups, updates and a support team that answers.',
                'description' => '<p>We keep your site secure and online: managed hosting, daily backups, dependency updates and a support channel with a real response time.</p>',
                'features' => ['Managed hosting and monitoring', 'Daily automated backups', 'Security patching', 'Priority support channel'],
                'cta_label' => 'See support plans',
                'cta_url' => '/contact',
            ],
        ];

        foreach ($rows as $i => $row) {
            Service::create($row + ['sort_order' => $i * 10, 'status' => 'published']);
        }
    }

    protected function seedProducts(): void
    {
        if (Product::count() > 0) {
            return;
        }

        $rows = [
            [
                'title' => 'Lindu CMS Business',
                'excerpt' => 'The full CMS platform for company websites, with page builder, blog, forms and SEO built in.',
                'description' => '<p>A complete company website you can run yourself. Includes the visual page builder, blog, media library, form builder, SEO controls and a full admin panel.</p>',
                'features' => ['Visual page builder', 'Blog, FAQ and testimonials', 'Form builder with email notifications', 'SEO and sitemap control', 'White-label branding'],
            ],
            [
                'title' => 'Lindu CMS Storefront',
                'excerpt' => 'An online store with catalogue, orders, payments and delivery workflow.',
                'description' => '<p>Everything needed to sell online: product catalogue, stock, orders, customer accounts and payment gateways for Indonesia and international cards.</p>',
                'features' => ['Catalogue with variants', 'Order management', 'Payment gateways', 'Customer accounts'],
            ],
            [
                'title' => 'Care & Maintenance Plan',
                'excerpt' => 'Monthly hosting, backups, updates and support for the site we built for you.',
                'description' => '<p>Sleep at night. We monitor, back up, patch and fix — with a response time you can hold us to.</p>',
                'features' => ['Managed hosting', 'Daily backups', 'Security updates', 'Support response SLA'],
            ],
        ];

        foreach ($rows as $i => $row) {
            Product::create($row + [
                'sort_order' => $i * 10,
                'status' => 'published',
                'cta_label' => 'Enquire',
                'cta_url' => '/contact',
            ]);
        }
    }

    protected function seedPortfolio(): void
    {
        if (Portfolio::count() > 0) {
            return;
        }

        $rows = [
            ['title' => 'Northwind Retail Website', 'client' => 'Northwind', 'category' => 'E-commerce', 'project_date' => '2025-11-15', 'excerpt' => 'Full storefront rebuild with faster checkout and a 40% lift in mobile conversion.', 'technology' => ['Laravel', 'MySQL', 'Tailwind CSS', 'Xendit'], 'url' => null],
            ['title' => 'Selatan Health Portal', 'client' => 'Selatan', 'category' => 'Corporate', 'project_date' => '2025-08-02', 'excerpt' => 'Patient information portal with appointment booking and clinic locator.', 'technology' => ['Laravel', 'MySQL', 'Alpine.js'], 'url' => null],
            ['title' => 'Bumi Logistics Dashboard', 'client' => 'Bumi', 'category' => 'Web App', 'project_date' => '2025-04-20', 'excerpt' => 'Internal operations dashboard tracking fleet, drivers and delivery performance.', 'technology' => ['Laravel', 'MySQL', 'Chart.js'], 'url' => null],
            ['title' => 'Anugrah Education Portal', 'client' => 'Anugrah', 'category' => 'Education', 'project_date' => '2024-12-10', 'excerpt' => 'Course catalogue, enrolment and progress tracking for 12,000 students.', 'technology' => ['Laravel', 'MySQL'], 'url' => null],
            ['title' => 'KopiKita Brand Refresh', 'client' => 'KopiKita', 'category' => 'Branding', 'project_date' => '2024-09-05', 'excerpt' => 'Brand identity and packaging system for a speciality coffee roaster.', 'technology' => [], 'url' => null],
        ];

        foreach ($rows as $i => $row) {
            Portfolio::create($row + [
                'sort_order' => $i * 10,
                'status' => 'published',
                'description' => '<p>'.$row['excerpt'].'</p><p>The project covered discovery, design, build and a structured handover with documentation and training for the internal team.</p>',
                'images' => [],
            ]);
        }
    }

    protected function seedTeam(): void
    {
        if (TeamMember::count() > 0) {
            return;
        }

        $rows = [
            ['name' => 'Ayu Prameswari', 'position' => 'Founder & Lead Strategist', 'bio' => 'Ayu leads the studio and works directly with clients on positioning, scope and delivery. Previously a product manager in fintech.'],
            ['name' => 'Bima Santoso', 'position' => 'Technical Director', 'bio' => 'Bima architects the systems behind our work and keeps the team honest about maintainability.'],
            ['name' => 'Citra Halim', 'position' => 'Design Lead', 'bio' => 'Citra runs the design practice — from brand identity through to interface design and design systems.'],
            ['name' => 'Dimas Yusuf', 'position' => 'Senior Backend Engineer', 'bio' => 'Dimas builds the APIs, data models and integrations behind our platforms.'],
            ['name' => 'Elsa Maharani', 'position' => 'Frontend Engineer', 'bio' => 'Elsa turns designs into fast, accessible interfaces.'],
            ['name' => 'Fajar Nugroho', 'position' => 'QA & DevOps', 'bio' => 'Fajar keeps releases boring — automated tests, staging environments and monitored deployments.'],
        ];

        foreach ($rows as $i => $row) {
            TeamMember::create($row + [
                'sort_order' => $i * 10,
                'status' => 'published',
                'social' => [],
            ]);
        }
    }

    protected function seedTestimonials(): void
    {
        if (Testimonial::count() > 0) {
            return;
        }

        $rows = [
            ['customer' => 'Rina Wijaya', 'company' => 'Northwind Retail', 'rating' => 5, 'testimonial' => 'They understood our business before they wrote a single line of code. The new site pays for itself every month.'],
            ['customer' => 'Andi Kusuma', 'company' => 'Selatan Health', 'rating' => 5, 'testimonial' => 'The handover was excellent — documentation, training and two weeks of support afterwards. Our team runs it confidently.'],
            ['customer' => 'Maya Sari', 'company' => 'Bumi Logistics', 'rating' => 4, 'testimonial' => 'The dashboard replaced three spreadsheets and a lot of arguing. Worth every rupiah.'],
            ['customer' => 'Hendra Gunawan', 'company' => 'Anugrah Education', 'rating' => 5, 'testimonial' => 'Twelve thousand students and still no complaints about speed. That is rare.'],
        ];

        foreach ($rows as $i => $row) {
            Testimonial::create($row + ['sort_order' => $i * 10, 'status' => 'published']);
        }
    }

    protected function seedClients(): void
    {
        if (Client::count() > 0) {
            return;
        }

        foreach ([
            'Northwind Retail', 'Selatan Health', 'Bumi Logistics', 'Anugrah Education',
            'KopiKita', 'Bintang pulsa', 'Merdeka Property', 'Cahaya Energi',
        ] as $i => $name) {
            Client::create(['name' => $name, 'sort_order' => $i * 10, 'status' => 'published']);
        }
    }

    protected function seedFaqs(): void
    {
        if (Faq::count() > 0) {
            return;
        }

        $rows = [
            ['category' => 'General', 'question' => 'How long does a typical project take?', 'answer' => "A company website on the CMS usually takes 4–8 weeks from kickoff to launch. E-commerce and custom applications run 8–16 weeks depending on scope. We give you a firm timeline after discovery."],
            ['category' => 'General', 'question' => 'Can we update the content ourselves after launch?', 'answer' => 'Yes — that is the point of the CMS. Pages, blog posts, services, products and FAQs are all editable from the admin panel, no code required. We also provide a training session for your team.'],
            ['category' => 'General', 'question' => 'Do you work with clients outside Jakarta?', 'answer' => 'Yes. We work with clients across Indonesia and Southeast Asia. Most of our process is remote, with video calls and shared workspaces.'],
            ['category' => 'Pricing', 'question' => 'How do you price projects?', 'answer' => 'We quote a fixed price after a free discovery call, so you know the cost before committing. Ongoing maintenance is a separate monthly plan if you want it.'],
            ['category' => 'Pricing', 'question' => 'Do you offer monthly payment plans?', 'answer' => 'For projects over a certain size, yes. We can split the payment across milestones. Ask us during the quotation.'],
            ['category' => 'Support', 'question' => 'What happens if something breaks?', 'answer' => "If you are on a maintenance plan, we monitor the site and fix issues before you notice them. Without a plan, we still fix it — you just pay a standard hourly rate."],
            ['category' => 'Support', 'question' => 'Do you offer a support guarantee?', 'answer' => 'Yes. Maintenance plans include a defined response time, from 4 hours for priority plans to 1 business day for standard plans.'],
            ['category' => 'Technical', 'question' => 'Which technologies do you build with?', 'answer' => 'Laravel and MySQL on the server side, with modern JavaScript on the front. The result is fast, secure and easy to maintain.'],
            ['category' => 'Technical', 'question' => 'Is the site mobile friendly?', 'answer' => 'Every site we build is responsive by default and tested on real devices, not just in a desktop browser.'],
        ];

        foreach ($rows as $i => $row) {
            Faq::create($row + ['sort_order' => $i * 10, 'status' => 'published']);
        }
    }

    protected function seedGallery(): void
    {
        if (GalleryAlbum::count() > 0) {
            return;
        }

        $albums = [
            ['title' => 'Office', 'slug' => 'office', 'description' => 'Where the work happens.'],
            ['title' => 'Team', 'slug' => 'team', 'description' => 'The people behind the projects.'],
            ['title' => 'Events', 'slug' => 'events', 'description' => 'Meetings, launches and workshops.'],
        ];

        foreach ($albums as $i => $album) {
            $a = GalleryAlbum::create($album + ['sort_order' => $i * 10, 'status' => 'published']);
            // No placeholder images are inserted: the operator uploads real
            // photos through the Media Library after installation.
        }
    }

    protected function seedCareers(): void
    {
        if (Career::count() > 0) {
            return;
        }

        $rows = [
            ['position' => 'Senior Laravel Developer', 'location' => 'Jakarta (Hybrid)', 'employment_type' => 'Full-time', 'description' => '<p>You will build and maintain web platforms for our clients, from marketing sites to e-commerce and internal tools.</p>', 'requirements' => ['5+ years of PHP and Laravel', 'Solid MySQL and query tuning skills', 'Comfortable with modern JavaScript', 'Experience with REST APIs', 'Care for clean, readable code']],
            ['position' => 'UI/UX Designer', 'location' => 'Jakarta', 'employment_type' => 'Full-time', 'description' => '<p>You will design interfaces and brand systems for a range of client projects, from early wireframes to polished UI.</p>', 'requirements' => ['Strong portfolio of web and mobile work', 'Fluent in Figma', 'Understanding of design systems', 'Ability to present work to clients']],
            ['position' => 'Project Manager', 'location' => 'Remote (Indonesia)', 'employment_type' => 'Full-time', 'description' => '<p>You will run client projects from kickoff to launch, keeping scope, timeline and stakeholders aligned.</p>', 'requirements' => ['3+ years managing digital projects', 'Excellent written and spoken English', 'Experience with agile delivery', 'Comfortable managing multiple clients']],
            ['position' => 'QA Engineer', 'location' => 'Jakarta', 'employment_type' => 'Contract', 'description' => '<p>You will write and maintain automated tests and verify releases before they reach clients.</p>', 'requirements' => ['2+ years in QA', 'Experience with Laravel/PHP testing', 'Attention to detail', 'Clear bug reporting']],
        ];

        foreach ($rows as $i => $row) {
            Career::create($row + [
                'sort_order' => $i * 10,
                'status' => 'published',
                'deadline' => now()->addMonths(2)->toDateString(),
            ]);
        }
    }
}
