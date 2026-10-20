# Deployment — zrakLJ

Navodila za objavo sprememb iz lokalnega projekta na VPS.

## 1. Lokalno — VS Code

Preveri spremembe:

```bash
git status
git diff
```

Dodaj spremembe in ustvari commit:

```bash
git add -A
git commit -m "Opis sprememb"
```

Pošlji na GitHub:

```bash
git push origin main
```

## 2. VPS — SSH

Poveži se s strežnikom prek SSH.

ssh root@185.187.170.83

Vpiši geslo, ki ga imaš shranjenega. 

Odpri mapo projekta:

```bash
cd /var/www/html/zrakLj-new
```

Prenesi zadnje spremembe z GitHuba:

```bash
GIT_SSH_COMMAND="ssh -i /root/.ssh/id_ed25519_github -o IdentitiesOnly=yes" git pull --ff-only origin main
```

Preveri stanje:

```bash
git status
```

## 3. Preverjanje po deploymentu

Odpri spletno stran:

https://zraklj.si/

Preveri spremembe!

## 4. CSV-podatki

CSV-datoteki na VPS:

```text
src/data/hourlyHistory.csv
src/data/dailyHistory.csv
```

Sta izključeni iz Gita z `.gitignore`.

**Nikoli ju ne prepisuj z lokalnima datotekama pri običajnem deploymentu.**

## 5. Cron jobi

Pregled:

```bash
crontab -l
```

Trenutna razporeditev:

| Čas | Naloga |
|---|---|
| Vsako uro ob :45 | Urne meritve |
| Vsako uro ob :55 | Ponovni poskus urnih meritev |
| Vsak dan ob 17:00 | Dnevne meritve |
| Vsak dan ob 19:00 | Ponovni poskus dnevnih meritev |

Cron jobi se izvajajo na VPS in niso del Git repozitorija.

## 6. Diagnostika

Zadnje urne meritve:

```bash
tail -n 9 /var/www/html/zrakLj-new/src/data/hourlyHistory.csv
```

Zadnje dnevne meritve:

```bash
tail -n 9 /var/www/html/zrakLj-new/src/data/dailyHistory.csv
```

Dnevniki:

```bash
tail -n 30 /var/www/html/zrakLj-new/logs/hourly.log
tail -n 30 /var/www/html/zrakLj-new/logs/hourly_retry.log
tail -n 30 /var/www/html/zrakLj-new/logs/daily.log
tail -n 30 /var/www/html/zrakLj-new/logs/daily_retry.log
```

## 7. Struktura CSV

**Urni podatki — 7 stolpcev:**

```text
čas, postaja, PM2.5, PM10, O3, SO2, NO2
```

**Dnevni podatki — 8 stolpcev:**

```text
datum, postaja, JA/NE (legacy), PM2.5, PM10, O3, SO2, NO2
```

Pri spremembah skript za shranjevanje vedno preveri, da se vrstni red stolpcev ujema s funkcijami za branje v `src/model.php`.