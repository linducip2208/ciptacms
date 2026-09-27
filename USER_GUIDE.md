# User guide

For the person who owns the website.

You do not need to know anything about programming to use this guide. Every
screen mentioned here exists in the system as shipped.

If you want the technical version of any of this, the same subjects are
covered in [ADMIN_GUIDE.md](ADMIN_GUIDE.md) (for whoever administers the
site) and [DEVELOPER.md](DEVELOPER.md) (for whoever writes code).

---

## Contents

1. [Signing in](#1-signing-in)
2. [How the website is put together](#2-how-the-website-is-put-together)
3. [Editing a page](#3-editing-a-page)
4. [Writing a blog post](#4-writing-a-blog-post)
5. [Services, products and portfolio](#5-services-products-and-portfolio)
6. [Your team, clients and testimonials](#6-your-team-clients-and-testimonials)
7. [Frequently asked questions and photos](#7-frequently-asked-questions-and-photos)
8. [Job openings and applications](#8-job-openings-and-applications)
9. [About us and contact details](#9-about-us-and-contact-details)
10. [Adding photos and documents](#10-adding-photos-and-documents)
11. [Forms and the messages people send you](#11-forms-and-the-messages-people-send-you)
12. [The menu along the top of the site](#12-the-menu-along-the-top-of-the-site)
13. [Colours and your company name](#13-colours-and-your-company-name)
14. [Things that need someone technical](#14-things-that-need-someone-technical)
15. [Getting help](#15-getting-help)

---

## 1. Signing in

Go to the address of your website and add `/login` at the end. For example
`https://example.com/login`.

Type your email address and password, then press **Sign in**.

- **I forgot my password.** On the sign-in page there is a **Forgot your
  password?** link. Enter your email address and the site will send you a
  reset link. If nothing arrives, ask your administrator to check that the
  website is able to send email.
- **I am asked for a six-digit code.** Your administrator has turned on
  two-factor security. The code comes from an app on your phone. You also get
  a set of one-time backup codes — keep them somewhere safe, because they are
  the way back in if you lose your phone.
- **The site says "Too many attempts".** Someone has tried to sign in with your
  email address too many times, or someone else has. The lock clears itself
  after a few minutes. Wait and try again.
- **Signing out.** There is a **Sign out** link in the top-right of the admin.
  Always sign out on a shared or public computer.

> **A note about the language.** The **public website** can be shown in more
> than one language. The **admin area — the screens you use to edit the site —
> is only available in English.** That is a limitation of this version, not a
> setting you have missed.

---

## 2. How the website is put together

Your website has these pages. Some of them you will never need to touch,
because they fill themselves in from the information you enter elsewhere.

| Page | What it shows |
|---|---|
| **Home** (`/`) | The welcome page. You can build this yourself with the page editor. |
| **About** (`/about`) | Your company story. Filled in from *About us* in the admin. |
| **Services** (`/services`) | The list of services you offer. |
| **Products** (`/products`) | The list of products you sell. |
| **Portfolio** (`/portfolio`) | Projects you have done, with an optional photo for each. |
| **Team** (`/team`) | The people who work for you. |
| **Testimonials** (`/testimonials`) | Nice things your customers have said. |
| **Clients** (`/clients`) | The logos of companies you have worked for. |
| **FAQ** (`/faq`) | Questions and answers, grouped by topic. |
| **Gallery** (`/gallery`) | Albums of photos. |
| **Careers** (`/careers`) | Job openings, with a form to apply. |
| **Blog** (`/blog`) | News posts and articles. |
| **Contact** (`/contact`) | Your address, phone and a message form. |

Two things are worth knowing:

- **Nothing is written into the page itself.** When you type a service on the
  *Services* screen, it appears on the website. You do not add it to a page.
- **Anything set to "draft" is invisible.** Every list here has a
  published/draft switch. A draft is invisible to visitors but still in your
  admin, so you can prepare something and publish it later.

You can also create extra pages of your own — a "Privacy Policy", for example.
Those live at `/p/your-page-name`. The menu and footer only show Privacy and
Terms once you have actually published them.

---

## 3. Editing a page

Go to **Content → Pages** in the sidebar.

- **To change an existing page**, click its name in the list.
- **To make a new page**, use the button to add one.

A page editor opens. It works like this:

- **The left-hand column is the toolbox.** Under *Text* you will find headings
  and paragraphs. Under *Media*, images, video and galleries. Under *Layout*,
  cards and grids. There are also ready-made pieces for your testimonials,
  your team, your contact details and your forms.
- **The big area in the middle is your page.** Drag a piece from the toolbox
  and drop it where you want it. You can drag things around afterwards to
  change the order.
- **Click any piece on the page** to change what it says. The right-hand panel
  shows the settings for whatever you clicked.
- **The top strip is a preview tool.** Choose *Desktop*, *Tablet* or *Mobile*
  to see how the page will look on that kind of screen. This also hides any
  section you have chosen to hide on a particular screen size.
- **Undo** puts back the last thing you did. There is a redo too. If you get
  lost, undo a few times.
- You can **copy** a piece, **paste** it, or **duplicate** it.
- **Save as block** lets you keep a piece you like and drop it onto other
  pages later.
- **Templates** are whole page layouts you can start from.

Before you press **Save**, use the **Preview** button to look at the real
rendered page. The canvas is an approximation; preview is the truth.

Every time you save a page, the system quietly keeps a copy of how it looked
before. If you break something badly, go to **Content → Revisions** and put the
old version back.

---

## 4. Writing a blog post

Go to **Content → Posts**, then add a new post.

Fill in:

- **Title** — the headline. This is what people see first.
- **Body** — the article itself. You can paste in text from Word or Google
  Docs and the formatting is kept.
- **Excerpt** — one or two sentences for the "read more" preview and for
  search engines. If you leave it blank, one is made from the start of the
  body.
- **Category** — file the post under a heading, e.g. *News* or *Case studies*.
- **Featured image** — the picture that appears at the top of the post and in
  the list. Choose one you have already uploaded (see section 10).
- **Publish** — choose *Published* when it is ready, or *Draft* to keep it
  hidden. The list of valid choices is Published, Draft and Scheduled.
- **Meta title / Meta description** — optional. These are what Google shows in
  its results. If you leave them blank, the title and excerpt are used.

The post's web address is worked out from the title. You can set it yourself
if you would rather have a particular address; whatever you type should be
short, use dashes instead of spaces, and must not clash with an existing post.

**Readers can comment.** Go to **Content → Comments** to read them, approve
them, mark them as spam, or delete them. You can also block words that should
never appear in a comment.

---

## 5. Services, products and portfolio

These three work the same way, under **Company Profile** in the sidebar. If
that group is not there, the company-profile module has been switched off —
ask your administrator.

### Services

Add each service with a title, a short description (the *excerpt*, which is
what people read in the list), a longer description, and a bullet list of
what is included. You can add a small icon, a picture, and a "call to action"
button with a link.

### Products

The same, plus a main picture and a gallery of extra pictures.

### Portfolio

Each project gets a title, the **client** it was for, a **category** (the
portfolio page can be filtered by it), a date, a link if you have one, a
description, the technologies used, and up to several images.

All three screens behave the same way:

| What you want to do | How |
|---|---|
| Add something | **+ Add** (top right) |
| Change something | **Edit** on the row |
| Hide it without deleting it | **Publish / Draft** toggle on the row |
| Make a copy to start from | **Copy** on the row |
| Get rid of something | **Delete** — this moves it to the bin first |
| Find something | the search box and the Published/Draft filter |

There is no drag-to-reorder and no sortable column headings on these lists —
the rows appear alphabetically, and the screens do accept `?sort=` and
`?dir=asc` in the web address if someone technical sorts them for you, but
there is no button to do it. If the order on your website matters, say so and
ask your administrator to set it.

### Deleted items

Deleted items are not destroyed straight away. Each company list has a
**Trash** link at the top, and everything you have deleted from that list is
waiting there — you can put it back, or delete it for good. This is
deliberate: a mistaken delete is recoverable. Pages and posts you delete go to
**Content → Trash** instead.

---

## 6. Your team, clients and testimonials

**Team** — one entry per person: their name, their job title, a photograph, a
short biography, and any social links (one per line, in the form
`network|address`, for example `linkedin|https://linkedin.com/in/name`).

**Clients** — the company names you have worked for, with a logo and a
website address. The home page shows up to twelve of them.

**Testimonials** — what a customer said, who said it, which company they are
from, an optional photo, and a star rating out of five.

> The About page also shows the first eight team members automatically, and a
> "years since we were founded" number. The founding year comes from the
> *General* settings screen.

---

## 7. Frequently asked questions and photos

**FAQ** — a question and an answer for each entry. Give each one a *category*
and the FAQ page groups them under those headings; anything without a category
lands under **General**. Leave the category blank rather than inventing one,
and the page still works.

**Gallery** — build albums first, then add images to each album. A gallery
album needs a title, a description and a cover picture. The gallery page lists
your albums; clicking one shows the photos inside it.

---

## 8. Job openings and applications

**Careers** — each job opening has a position, a location, the type of
employment, an optional closing date, a description, and a list of
requirements.

When you set a closing date in the past, the site stops accepting applications
for that job and says so, rather than quietly accepting people.

Applications arrive under **Company Profile → Applications**. Each one shows
the applicant's name, email, phone, covering letter, and their uploaded CV as
a PDF or Word document (up to 4 MB). You can move each one through
*Received → Reviewing → Shortlisted → Rejected*, or mark it *Hired*.

Your administrator should delete CVs once a decision has been made — they are
personal data about real people.

---

## 9. About us and contact details

These are two forms rather than lists.

**About us** — a description of your company, plus separate boxes for your
history, your vision, your mission and your values. The About page is built
from these.

**Contact** — your address, phone number, WhatsApp number, email address, a
map (paste the embed code from your map provider, for example Google Maps),
your opening hours, and your social links. These fill in the Contact page, the
footer, and the contact block on any page you build.

---

## 10. Adding photos and documents

Go to **Media → Library**.

- Choose your files (you can pick more than one at a time) and press
  **Upload**.
- **How big?** The limit is 10 MB per file by default. Your administrator can
  raise it.
- The library shows the pictures, documents and videos separately, and you can
  search it and sort it.
- **Folders** let you keep things organised, and you can put one folder inside
  another.
- Click any item to change its **title**, **description** or **caption**, and —
  importantly — its **alternative text**.

> **Alternative text (alt text) is not decoration.** It is what a screen
> reader reads out instead of the picture. If you paste a photo of your team
> and leave it blank, anyone using a screen reader will hear nothing at all.
> One short sentence per image is plenty: "Our workshop in Bandung, 2025".

The system makes smaller versions of your images automatically, so you do not
need to resize anything before uploading. Converting to modern formats
(WebP, AVIF) happens in the background — a busy queue means they are still
being made.

**If an image does not appear on the site after you upload it, tell your
administrator.** It almost always means one setup step is missing, and it is a
two-minute fix.

---

## 11. Forms and the messages people send you

There are two kinds of message you receive.

### The contact form

The **Contact** page has a message form. Messages arrive under
**Company Profile → Messages**, with the sender's name, email, phone,
subject and message. Mark each one as *New*, *Read*, *Replied* or *Archived*
as you work through them.

### Other forms

You can also build your own forms — an enquiry form, a quote request, a
sign-up. Go to **Forms → Forms** and add one.

1. Give it a name. The system works out a web address for it, which is how
   the form is identified. **Write it down.**
2. Add fields by dragging them onto the form. There are ordinary ones — name,
   email, phone, a paragraph of text, a drop-down, tick boxes, a date — and
   ones that accept a file.
3. Fill in the help text and the placeholder for each field. Placeholder text
   is the grey hint that disappears when someone starts typing.
4. Drag the fields into the order you want them.
5. Set the message visitors see after they press Send.
6. Put the form on a page: add the **Form** piece from the page editor's
   toolbox, then choose which form it should be.

Submissions arrive under **Forms → Submissions**. You can read each one, and
export the lot to a spreadsheet.

**Spam protection** is on by default: a hidden field that only a robot fills
in, a minimum time before the form will accept an answer, a blocked-word list,
and a limit on how many times one person can submit. Your administrator can
adjust these under **Forms → Spam / Protection**.

**A form that is not working** is nearly always because it has no fields, or
because it was never given a web address. Check both first.

---

## 12. The menu along the top of the site

Go to **Appearance → Menus**.

The screen shows one menu at a time, chosen by the `?location=` part of the
address:

| `location=` | What it controls |
|---|---|
| `admin` | the sidebar you are looking at right now — leave it alone |
| `primary` | **the menu along the top of the website and the links in the footer** — this is the one you want |
| `footer` | nothing. It is saved and never shown anywhere. Ignore it. |

To add a top-level item quickly, fill in the small form at the top of the list
(**Title**, **URL**, **Icon**, **Permission**) and press **Add**.

To do more — set a parent, a sort position, whether the item is visible, a
badge, or open in a new tab — use the full form at
`/admin/menus/create?location=primary`:

- **Title** — the words people see.
- **URL** — where the link goes. Type it as `/services`, not as the whole
  `https://…` address. (You can use a named route instead, but a plain path is
  easier.)
- **Parent** — leave this empty for a top-level item, or choose another item to
  make this one sit inside it. Top-level items with children turn into
  drop-downs.
- **Sort order** — smaller numbers appear first.
- **Visible** — untick it to hide the item without deleting it.
- **Badge** — a small coloured label next to the item.

**Things that surprise people:**

- There is no drag-and-drop for menus. The sort order and parent fields are
  how you arrange them, and there is no edit button on each row — you edit by
  opening the item's address.
- If you make an item its own parent by accident, the site refuses and tells
  you so. Good.
- You cannot delete a group that still has items inside it. Move or delete them
  first.
- Deleting a menu item really deletes it. There is no bin.
- The list updates straight away in the browser, but the website itself may
  take up to two minutes to catch up, because the menu is cached. If it seems
  stuck, wait and refresh.
- An item can be marked as a **button** instead of a plain link, which is how
  the "Contact" link at the end of the menu is styled. Your administrator can
  change that.
- The footer shows only the **first three** groups on the menu that have items
  in them, plus a contact column. If you add a fourth top-level group, it may
  appear in the menu but not in the footer.
- Some menu entries are created by modules, and they disappear if the module is
  switched off. That is not something you broke.

---

## 13. Colours and your company name

**Appearance → Themes → Customize** is where you change colours. There are
groups for colours, for text (font, size, weight) and for layout (page width,
corner rounding, spacing). Fill in a value, save, and look at the website.

**Settings → Branding** changes the things people actually read: your company
name, the logo at the top, the small icon in the browser tab, the footer text,
and the main brand colours.

Two honest warnings:

- The colours here change the website, and the login screen picks up the
  company name — but **not** your logo. The sign-in page uses a simple letter
  badge instead.
- Saving a colour here does not change the colours in the admin area. That is
  deliberate, so you can always find your way around the admin.

---

## 14. Things that need someone technical

These are real. They are not things you have done wrong, and there is no
button for them.

| | |
|---|---|
| **The admin area is in English only** | The public website can be multilingual; the editing screens cannot. Changing this is development work. |
| **Page sections always stack in one column** | You can choose a multi-column layout in the editor and the editor will show it, but the published page renders in a single column. |
| **Hiding a section on a phone or tablet** | The editor honours this while you work; the published page does not. It is a known bug. |
| **Some settings do nothing** | The sender name on outgoing email, the text on the 404 and error pages, and the "hide vendor branding" switch are stored but nothing displays them yet. Your administrator can wire them up. |
| **A second theme does not look different** | CipaCMS ships two themes. The second one, `dark-commerce`, does not yet have its own colours, so switching to it changes almost nothing. |
| **The payment gateway screens** | The settings for online payments are stored but there is no checkout, no invoice and no subscription. Do not advertise online payment as working. |
| **Changing the address of the website** | That is a hosting and domain-names change, not a setting. |

---

## 15. Getting help

When you report a problem, these four things make it possible to fix quickly:

1. The **exact address** of the page, copied from the browser.
2. **What you did** and **what you expected to happen**.
3. A **screenshot** of what you saw.
4. The **time** it happened, to within the hour.

Do not send anybody your password, and be careful with screenshots — they can
contain personal information belonging to your customers.

If you are not sure whether something is a fault or just how the site works,
check the "Things that need someone technical" section above first — several
things that look like faults are documented, deliberate limits of this version.

---

## See also

[ADMIN_GUIDE.md](ADMIN_GUIDE.md) — for whoever administers the site
[DEVELOPER.md](DEVELOPER.md) — for whoever writes code
[INSTALL.md](INSTALL.md) · [UPGRADE.md](UPGRADE.md) ·
[TROUBLESHOOTING.md](TROUBLESHOOTING.md)
