# Gluta RepubliQ clinic system: staff guide

This guide is for the people who run the clinic day to day. Each part says who it is for. You only see the screens your role allows, so if a menu item below is missing, your role does not include it. Ask the owner if you need it.

Sign in at **/login** on the clinic website (the "Staff login" link in the footer).

---

## 1. Everyone

### Signing in safely

- Use your own account. Never share a password: the audit log records who did what.
- Turn on **two-factor authentication** under your name (bottom left) > **Settings** > **Security**. Scan the code with an authenticator app (Google Authenticator, Microsoft Authenticator) and keep the recovery codes somewhere safe.
- Or add a **passkey** on the same page, and sign in with your phone's fingerprint or face instead of a password.
- Forgot your password? Use "Forgot password" on the sign-in page, or ask the owner to set a new one.

### Finding things fast

Press **Ctrl + K** (Cmd + K on a Mac) anywhere in the office to jump to a screen or search leads by name, phone or email.

---

## 2. Reception

### The day's agenda

**Dashboard** shows today's appointments in time order. Change a visit's status straight from the list: pending, confirmed, checked in, in consultation, in treatment, completed, cancelled, no show or rescheduled.

### Appointments

**Appointments** shows one day at a time. Use the arrows or the date picker to move between days and the branch filter to see one clinic.

- **New appointment**: pick the client (or type a new one), treatment, branch, doctor or "first available", date and time. Only free times are offered, so a doctor can never be double booked.
- **Move** a visit to another time or doctor from its row.
- **Check in** when the client arrives.

Online bookings from the website appear here by themselves, marked _pending_. Call or message the client and set them to _confirmed_.

### Leads and enquiries

Every website enquiry, booking and chat becomes a **lead**, with where it came from (Facebook, Google, a campaign link) and whether the person agreed to be contacted.

- **Leads** lists them by stage: New, Contacted, Consultation booked, and so on up to VIP. Click the stage tabs to filter, or search by name, phone or email.
- Open a lead to move it along the pipeline (click a stage), add notes, and see its full history.
- Leads are for sales follow-up only. Never write medical details on a lead.
- **Client record**: on a lead, staff who use the till see visits, last visit, amount paid to date and every purchase. **Ring up a sale** from the lead so the sale lands on their record.
- **Contact details are masked** ("0917 ••• ••34") for everyone but the owner. Press **Show contact details** on the lead to see one client's number and email; each time is written to the audit log. Only the owner can **Export clients** (a spreadsheet of every client with contacts), and that is logged too.
- Staff tied to a branch see their branch's leads plus enquiries that did not name a branch.

### Notifications

The bell at the top right shows new alerts for your work: website enquiries and chats (people who handle leads), online bookings (people who handle the diary, for their own branch) and stock to count (stock staff). Click one to open it.

### Website chat

**Chat** is the inbox for the chat window on the website. The number on the menu shows chats waiting. Reply from the conversation screen; the visitor sees it within seconds. Close a conversation when it is done.

---

## 3. Cashier

### Ringing up a sale (Point of sale)

1. Open **Point of sale**.
2. Search or filter the list and tap treatments and products to add them. Prices come from the catalogue and cannot be typed in. Services with a price list appear once per line, for example "9D HIFU (Face & Neck)" or "Bronze Gluta Drip (5+1 package)", each at its listed price.
3. Optionally pick the client and a promotion, and add a discount (percent or fixed amount).
4. Choose the payment method: cash, card, GCash, Maya, bank transfer or other.
5. Enter the amount received. For cash the change is shown. A part payment (deposit) leaves a balance on the sale.
6. Complete the sale. The receipt opens; print it from there.

### Refunds

Open the sale (**Point of sale** > **Sales**, then the sale) and use the refund panel: amount, method and reason. Returned products go back on the shelf. Every refund is written to the audit log.

### Daily sales and takings

**Sales** lists one day's sales with totals taken and still owed. **Export day** or **Export month** downloads a spreadsheet (CSV) for the books.

---

## 4. Inventory

**Inventory** shows stock per branch, with alerts at the top for items that are **expired**, **expiring within 60 days**, **low** or **out of stock**.

- **Receive**: record a delivery with product, branch, quantity, lot number, expiry date, cost and supplier. Add a new supplier from the same form.
- **Adjust**: record damage, expired stock, stock used in a treatment, a count correction, or a **transfer** to another branch.
- Open a product to see its lots in expiry order and every movement in and out.
- The system always uses the lot that expires first, when selling and when stock is used in treatments.
- **Count stock**: a stocktake sheet for the whole branch. Type what is on the shelf (blank rows are left alone); anything that differs is corrected as one stocktake.
- **Count these**: services rung up when the records held too little of a recipe product. Count the product (**Count** link) and press **Mark as counted**.
- **Records disagree**: every night the system checks that the shelf count, the lots and the stock ledger agree. A product listed here needs counting; the flag clears on the next check after the count.

**Catalogue** holds the treatment menu and retail products: names, prices, promo prices, photos and whether each one is shown and sellable.

---

## 5. Branch manager and owner

### Dashboard figures

Below the agenda, the analytics panel shows revenue, gross profit, average sale, refunds, bookings by status, top treatments, payment mix and enquiry conversion over the last 7, 30, 90 or 180 days. Gross profit leaves out wages, rent and running costs, and treatments carry no cost of goods, so it reads higher than the real margin.

### Staff (HR)

**Staff** is the employee list: personal and job details, branch, pay and photo.

- Tick **Practitioner** and **Show on the website** to list a doctor or specialist on the website's Doctors and About pages, with their photo, title and focus.
- **Schedule**: the week's shifts per person. Click a day to set a shift (use the 4 to 12 hour buttons for quick lengths), a rest day, or a night shift (an end time earlier than the start ends the next morning). **Copy last week** repeats the rota without touching days already planned.
- **Time record**: everyone scheduled or clocked in that day, with their shift, time in and out, **late** (more than 5 minutes after the shift start), **absent** (shift started, no clock-in), hours and overtime. Overtime counts against the scheduled shift, or 8 hours when there is none. A kiosk clock-in shows the photo taken.
- **Branch kiosk (DTR)**: open **/kiosk** on the branch tablet once, pick the branch and bookmark the page. Staff clock in and out there with their employee number and **kiosk PIN**; clocking in needs a photo from the tablet camera. Five wrong PINs lock that number out for 5 minutes. Set or reset a person's PIN on their Staff page (HR never sees the old one).
- **Leave**: staff request leave; a manager approves or declines it.
- **Resign** someone when they leave (they disappear from the website and from payroll), or reinstate them.

### Payroll

1. **Payroll settings**: pay schedule and the SSS, PhilHealth and Pag-IBIG contribution rates.
2. Add one-off **adjustments** (bonus, deduction, cash advance) for the period.
3. Start a **payroll run** for a period. Check it, then **Approve**, then **Pay**.
4. Each person's **payslip** can be opened and printed from the run.

### Accounting

- **Chart of accounts**: the account list with balances.
- **Journal entries**: post an entry (debits must equal credits). A posted entry is never edited: **void** it and post a correct one. Sales and payroll post their own entries.
- **Close a month** once its books are final; nothing more can be posted into it.
- **Reports**: trial balance, income statement and balance sheet. Pick **Month**, **Quarter** or **Year** (step back and forward with the arrows) and the income statement sits next to the period before it, with the change per line; **Custom** compares any dates with the same dates a year earlier. **Export CSV** downloads all three, comparison included, for your accountant.

### Branches

**Branches** holds each clinic's address, phone, email, map link and **opening hours**. The hours you save here are the ones on the website (with the live "Open now" or "Closed now" badge) and the only times online booking offers. Untick "Show on the website and take bookings" to hide a branch.

### Staff accounts

**Staff accounts** decides who can sign in and what they see.

- **Add an account**: name, email, role and a first password. Give it to the person in person; they can change it and turn on two-factor.
- Roles: Organization Owner and Clinic Administrator (everything), Branch Manager, Receptionist, Cashier, Inventory Manager, HR Manager, Accountant, Marketing Manager, and the clinical roles (Doctor, Nurse, Aesthetician, Therapist), who see appointments. The form shows which screens each role opens.
- When someone leaves, **switch off** their account instead of deleting it: they are signed out at once, and their sales and notes keep their name.

### Website traffic

**Website traffic** counts visits to the public website by itself (no outside tracker, no cookies, no IP address kept): visitors, page views, the daily trend, top pages, where visitors came from (Google, Facebook, Instagram, TikTok, a campaign link or direct), campaigns, devices, the share who sent an enquiry, and a log of the latest 50 page views. Signed-in staff and search-engine robots are not counted. Tag ad and post links with `?utm_source=facebook&utm_campaign=your-promo` to see each campaign separately.

### Website pages

**Website pages** edits the privacy policy, terms of use and data privacy notice. Each section has a heading and text; leave a blank line between paragraphs. Saving publishes immediately and updates the "last reviewed" date. Have your lawyer approve the wording.

### Clinic settings

Social media links shown on the website, and whether the website chat is switched on and how it greets visitors.

**Webhooks** (owner only): an https address that receives new enquiries, online bookings, sales and stock alerts as they happen, for example a CRM or Zapier. Requests are signed with the key shown (header `X-Clinic-Signature`, HMAC-SHA256 of the body). They include client contact details, so only use a system the clinic controls. **Send a test** shows what came back.

### Audit log

**Audit log** lists sign-ins, failed sign-in attempts, refunds, stock corrections, payroll, accounting, account and settings changes: who, what, when and from which address. Nothing in it can be edited or deleted.

---

## 6. What the website does on its own

- Visitors can book without an account: treatment, branch, doctor or first available, date and a live free time. They get a confirmation page with a booking reference.
- Every booking and enquiry lands in **Leads** with its source and consent.
- Before and after photos are labelled as illustrations, and no page promises results.
