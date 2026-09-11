# DigitalOcean Migration

Moving USSCOS off GoDaddy cPanel onto a DigitalOcean droplet.

**Do this while the data is still test data.** No downtime to plan, no real orders to
protect, no customers to notify. It gets materially harder after go-live.

The old host stays running and untouched throughout. DNS is the last thing to change,
and it's the only step customers would notice.

---

## 1. Create the droplet

DigitalOcean → **Create → Droplets**

| Setting | Choose |
|---|---|
| Region | New York or Atlanta (closest to Georgia) |
| Image | **Ubuntu 24.04 (LTS) x64** |
| Type | Basic → Regular SSD |
| Size | **2 GB / 1 CPU / 50 GB** — $12/mo, plenty for this |
| Authentication | **SSH key** (not password — see below) |
| Hostname | `usscos-prod` |
| Backups | **Enable** — $2.40/mo, worth every cent |

### SSH key

On your Mac:

```bash
ssh-keygen -t ed25519 -C "chip@usscproducts.com"   # press Enter through the prompts
pbcopy < ~/.ssh/id_ed25519.pub                      # copies the public key
```

Paste that into DigitalOcean's **New SSH Key** box. Password logins are a standing
invitation to brute-force bots; keys aren't.

## 2. Provision the server

```bash
ssh root@<droplet-ip>
git clone https://github.com/USSCPROD/usscos.git /tmp/usscos
bash /tmp/usscos/deploy/provision.sh
```

That installs nginx, PHP 8.3-FPM, MySQL, Composer, certbot, ufw, and fail2ban; creates
the `deploy` user and the directory layout; creates the database; and enables the
firewall. It prints the generated database password and also saves it to
`/root/.usscos-db-password`.

Roughly five minutes, and it's safe to re-run.

## 3. Deploy the application

```bash
rm -rf /tmp/usscos
sudo -u deploy git clone https://github.com/USSCPROD/usscos.git /var/www/usscos
cd /var/www/usscos
sudo -u deploy composer install --no-dev --optimize-autoloader
sudo -u deploy cp .env.example .env
sudo -u deploy nano .env
```

Fill in `.env`:

```ini
APP_NAME="USSCOS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://os.usscos.com

PUBLIC_SITE_PATH=/var/www/usscos.com

DB_HOST=localhost
DB_DATABASE=usscos
DB_USERNAME=usscos
DB_PASSWORD=<from /root/.usscos-db-password>

SESSION_SECURE=true
```

Then the website files:

```bash
sudo -u deploy cp -r /var/www/usscos/website/* /var/www/usscos.com/
```

## 4. nginx

```bash
cp /var/www/usscos/deploy/nginx-os.usscos.com.conf /etc/nginx/sites-available/os.usscos.com
cp /var/www/usscos/deploy/nginx-usscos.com.conf    /etc/nginx/sites-available/usscos.com

ln -s /etc/nginx/sites-available/os.usscos.com /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/usscos.com    /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl reload nginx
```

## 5. Move the database

On the **old** host — cPanel → phpMyAdmin → select the database → **Export** → Go.

Upload the `.sql` file to the droplet and import it:

```bash
scp ~/Downloads/usscos.sql deploy@<droplet-ip>:/tmp/
ssh deploy@<droplet-ip>
mysql -u usscos -p usscos < /tmp/usscos.sql
rm /tmp/usscos.sql
```

Then copy across any uploaded files (product images and documents) from
`public_html/businessos/public/uploads/` into `/var/www/usscos/public/uploads/`.

## 6. Test before touching DNS

Point your own Mac at the droplet without affecting anyone else:

```bash
sudo nano /etc/hosts
```

Add — substituting the droplet IP:

```
203.0.113.10   os.usscos.com
203.0.113.10   usscos.com
```

Now your browser reaches the new server while the rest of the world still gets GoDaddy.
Work through it properly: log in, open a customer, a product with images and documents,
a quote, run Ship & Invoice, submit a lead form, confirm the lead arrives.

**Remove those two lines from `/etc/hosts` when you're done testing.**

## 7. Cut over

Only once step 6 passes.

GoDaddy → **DNS** for usscos.com. Change the A records to the droplet IP:

| Type | Name | Value |
|---|---|---|
| A | `@` | droplet IP |
| A | `os` | droplet IP |
| CNAME | `www` | `usscos.com` |

Drop the TTL to 600 seconds an hour beforehand so the switch propagates quickly.

Once DNS resolves to the droplet, issue certificates:

```bash
certbot --nginx -d os.usscos.com -d usscos.com -d www.usscos.com
```

Certbot edits the nginx configs to add TLS and the HTTP→HTTPS redirect, and installs a
renewal timer.

## 8. After the switch

- The webhook can finally use HTTPS. In `/var/www/usscos.com/config.php`:
  `define('USSCOS_WEBHOOK', 'https://os.usscos.com/webhook/lead');`
  (The `http://` workaround existed only because cPanel blocked loopback HTTPS.)
- Keep the GoDaddy account for a month as a fallback.
- Verify the DigitalOcean backup is running.

---

## Deploying from then on

```bash
ssh deploy@<droplet-ip>
cd /var/www/usscos && bash deploy/deploy.sh
```

Pulls `main`, installs dependencies, fixes permissions, reloads PHP-FPM. It refuses to
run if someone has edited files directly on the server, and it warns you when a deploy
contains new migrations — those stay manual, so a schema change is always deliberate and
always follows a backup.

## What this buys you

| | cPanel today | Droplet |
|---|---|---|
| Deploys | Manual file upload | `git pull` |
| Rollback | None | `git reset --hard <commit>` |
| HTTPS loopback | Blocked | Works |
| `BASE_PATH` | Hardcoded per environment | Resolved automatically |
| Upload access control | **None — any URL is public** | Can be moved behind PHP auth |
| Cron | Awkward | Native, for the AI brief and stock alerts |
| Staging | Not possible | A second droplet or subdomain |
| SSH | Limited | Full |

## Still to do after the move

**Serve uploads through PHP.** `provision.sh` creates
`/var/www/usscos-storage/uploads` outside the web root ready for this, but the code
still writes into `public/uploads`. Today, `product_documents.is_public` only controls
whether the UI *shows* a file — the file itself is fetchable by anyone with the URL,
logged in or not. Fixing that means writing uploads to the private directory and adding
a controller that checks authentication and the `is_public` flag before streaming the
file. It's the main security gap the move makes solvable.

## Cost

| | |
|---|---|
| Droplet (2 GB) | $12.00 |
| Backups | $2.40 |
| **Total** | **~$14.40/month** |
