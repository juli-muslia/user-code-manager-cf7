# User Code Manager for Contact Form 7 – Configuration Guide

## Plugin Requirements

Make sure the following plugins are installed and activated:

- Contact Form 7  
- Contact Form 7 Dynamic Text Extension  
- Advanced Custom Fields (ACF)  
- Admin Columns  
- ACF Admin Columns  

---

## Setup Instructions

### Step 1: Create ACF Post Type

1. Go to **Custom Post Types → Add New**  
2. Create a new post type (e.g., `Invitations`)  
3. **Note the post type slug** – you'll need this later  

---

### Step 2: Create ACF Fields

Create the **required fields** in ACF for your post type.  
You can name them as desired, but make sure they match the plugin settings later.

Recommended fields:

- `first_name` (Text) – First name of invitee  
- `last_name` (Text) – Last name of invitee  
- `email` (Email) – Email address  
- `invitation_code` (Text) – Unique invitation code  
- `status` (Select) – Status with options: `unused`, `used`  
- `uuid` (Text) – Unique identifier for URL  
- `invitation_url` (Text) – Generated invitation URL  
- `qr_code` (Image) – Generated QR code  

---

### Step 3: Create Contact Form 7 Form

Add the following fields to your Contact Form 7 form:

```html
[dynamic_text* first_name placeholder:First%20Name "ucm_acf_field key='your_acf_field_name'"]
[dynamic_email* e-mail placeholder:E-Mail "ucm_acf_field key='your_acf_email_field_name'"]
```

To make the invitation code field hidden, use:

```html
[hidden invitation-code ""]
```

---

### Step 4: Configure Plugin Settings

1. Go to **Contact Form 7 → Your Form → Unique Code Validator** tab  
2. Fill in the settings:

    - **CF7 Email Field**: `user-email`  
    - **CF7 Unique Code Field**: `invitation-code`  
    - **ACF Post Type**: Your post type slug  
    - **ACF Field Names**: Must match the field names you created  
    - **CF7 Page Location**: The URL where the form is located  

3. For first-time setup:

    - Click **Reset All Invitation Codes** – this generates the invitation codes and sets the status to `unused`  
    - Click **Generate URL & QR Codes** – this will create the unique URLs and QR codes for each invitation  

4. Click **Save Settings**

---

## ⚠️ Important Notes

- Field names must match exactly between ACF and the plugin settings  
- The `status` field must use `unused` and `used` as values  
- Generate URL & QR codes after adding new invitations  
- Test the form submission process before going live  

---

## Available Shortcodes

Use the following shortcode to display ACF field values in your CF7 forms:

```html
[dynamic_text* first_name placeholder:First%20Name "ucm_acf_field key='your_acf_field_name'"]
```

> Note: `%20` is used to represent spaces in placeholder values.

---

## Managing Invitations

- Use the **Export CSV** button to download invitation data  
- **Reset All Invitation Codes** – regenerates codes for all entries  
- **Generate URL & QR Codes** – creates new URLs and QR codes  
- Individual invitation codes can be reset using the **Reset Code** button  

---

## 🔁 Backup Reminder

**Always backup your data** before performing bulk operations like resetting codes or generating new URLs.
