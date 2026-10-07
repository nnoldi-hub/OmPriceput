# Plan de dezvoltare - sistem Om Priceput (contractor de meserii)

## 1. Evaluare inițială

Structura propusă este corectă și adecvată pentru un proiect de tip firmă de servicii/meserii. Modularizarea în 4 componente principale este solidă:

- Modul Public: site SEO + lead generation
- Modul Vânzări: CRM, oferte, pipeline
- Modul Tehnic: instalări, suport, echipamente
- Modul Administrativ: facturi, utilizatori, KPI, setări

Această arhitectură reduce complexitatea și permite dezvoltarea incrementală, fără a genera dependențe inutile între module.

---

## 2. Strategia de dezvoltare pe etape

Scopul este să se construiască produsul în etape logice, astfel încât progresul să fie ușor de urmărit și verificat. Fiecare etapă va avea obiective clare, rezultate acceptate și stare de finalizare.

### Legendă de stare

- [ ] Neînceput
- [ ] În lucru
- [x] Finalizat

---

## 3. Etapele de dezvoltare

### Etapa 0 - Foundation / arhitectură de bază

Obiectiv: pregătirea bazei tehnice și a structurii aplicației.

- [x] Configurare proiect Laravel
- [x] Configurare autentificare și roluri (admin, vânzări, tehnic, suport)
- [x] Setup baze de date și migrări principale
- [x] Definire model de date de bază: clienți, oferte, echipamente, instalări, facturi
- [x] Structură modulară pe directoare / modules
- [x] Configurare autentificare Inertia + Vue 3 + Tailwind
- [x] Setare convenții de cod și workflow de dezvoltare

Rezultat acceptat: aplicația are fundația stabilă și permisiunile definite.

---

### Etapa 1 - Modul Public (SEO + lead generation)

Obiectiv: lansarea website-ului public și capturarea de lead-uri.

- [x] Pagini principale: acasă, despre firmă, servicii, contact
- [x] Pachete de servicii: entry / medium / premium
- [x] Formular de cerere de deviz (`/cerere-deviz`) cu upload de poze, în locul configuratorului de camere
- [x] Calculator de distanță / cablu
- [x] Formular de cerere ofertă
- [x] Blog / articole SEO
- [x] Optimizare SEO pe pagini cheie
- [x] Integrare email/CRM pentru lead-uri
- [ ] Testare UX și conversie (testat funcțional automat; rămâne de făcut testare vizuală/conversie cu utilizatori reali)

Rezultat acceptat: vizitatorul poate sa vizualizeze oferta, să compare pachete și să trimită solicitarea pentru ofertă.

---

### Etapa 2 - Modul Vânzări (CRM + oferte)

Obiectiv: digitalizarea activității comerciale.

- [x] CRM clienți
- [x] Gestionare leads și contacte
- [x] Pipeline vânzări: lead → ofertă → contract (schimbare status ofertă; acceptarea promovează automat clientul din lead)
- [x] Generator de oferte (template-uri, PDF)
- [x] Istoric oferte și statusuri
- [x] Export Excel / raportare
- [x] Notificări email/SMS (email funcțional; SMS necesită un provider extern - vezi Etapa 5)
- [x] Filtre și căutare rapidă în baza de date
- [x] Dashboard comercial simplu

Rezultat acceptat: echipa de vânzări poate gestiona clienții și generarea ofertelor fără documente separate.

---

### Etapa 3 - Modul Tehnic (instalări + suport)

Obiectiv: gestionarea echipamentelor, programărilor și intervențiilor tehnice.

- [x] Management echipamente: consumabile, scule, accesorii, piese
- [x] Tracking stoc și inventar
- [x] Programare instalări
- [x] Programări intervenții (camp `type`: instalare/interventie pe aceeasi entitate)
- [x] Ticketing pentru suport clienti
- [x] Hărți Google Maps pentru locații (embed fara API key, plus link catre Google Maps)
- [x] Checklist instalare
- [x] Raport tehnic PDF
- [x] Asignare tehnicieni pe proiecte

Rezultat acceptat: echipa tehnică are toate informațiile necesare pentru instalare, suport și raportare într-un singur loc.

---

### Etapa 4 - Modul Administrativ

Obiectiv: controlul operațional și financiar al firmei.

- [x] Dashboard general KPI
- [x] Facturare și plăți
- [x] Abonamente mentenanță
- [x] Management utilizatori și permisiuni
- [x] Setări generale sistem
- [x] Raportări vânzări + tehnic + financiar
- [x] Alerte și monitorizare performanță

Rezultat acceptat: managementul firmei are vizibilitate completă asupra operațiunilor și performanței.

---

### Etapa 5 - Integrări și automatizări

Obiectiv: conectarea sistemului la instrumentele externe necesare.

- [x] Google Maps API (embed fara cheie API, din Etapa 3; API JS completa cu autocomplete/geocoding ramane optionala daca se doreste, necesita cheie Google Cloud de la client)
- [x] Email și SMS notifier (email functional; SMS cu driver "log" implicit + arhitectura gata pentru provider real - vezi Setari > Log SMS)
- [x] Export PDF / Excel (extins cu export abonamente)
- [x] Search și filtrare avansată (cautare globala cross-modul in bara de navigare, pe langa filtrele existente din fiecare lista)
- [ ] Integrare pentru facturare / plăți (decizie explicita: se ramane la marcare manuala "platita"; procesator real (Stripe/Netopia/PayU) necesita cont si credentiale de la client, de adaugat cand este nevoie reala)
- [x] Automatizări workflow: lead → ofertă → contract → instalare (oferta acceptata creeaza automat instalarea; instalarea finalizata creeaza automat factura)

Rezultat acceptat: procesele repetitive sunt automatizate și nu depind de intervenția manuală.

---

### Etapa 6 - Testare, securitate și lansare

Obiectiv: validarea produsului înainte de livrare.

- [x] Testare funcțională pe module (109 teste automate + verificare manuala in browser pentru fiecare etapa)
- [x] Testare fluxuri de business reale (lead→oferta→instalare→factura verificat integral; a scos la iveala si reparat bug-ul cu verificarea de email pentru utilizatori creati de admin)
- [x] Testare permisiuni și roluri (a scos la iveala si reparat: rolul "suport" nu avea acces la niciun modul, desi tichetele de suport ii erau destinate)
- [x] Verificare securitate - revizuire manuala (fara istoric git pentru unelte automate bazate pe diff); reparat: rate limiting lipsa pe formularul public de oferta si pe resetarea parolei, inregistrare publica dezactivata (aplicatie interna, conturile se creeaza din Admin > Utilizatori)
- [x] Testare responsivitate și UX (a scos la iveala si reparat un bug real: tabelele foloseau `overflow-hidden` care ascundea coloane intregi pe mobil, in loc de scroll orizontal - corectat pe 9 pagini)
- [~] Deploy pe mediu de producție (verificat local ca `route:cache`/`config:cache`/`view:cache` functioneaza corect; deploy-ul efectiv pe un server necesita hosting/domeniu de la client)
- [x] Monitorizare și ajustări finale (alerte admin din Etapa 4, log SMS din Etapa 5; toate problemele gasite in aceasta etapa au fost reparate)

Rezultat acceptat: aplicația este stabilă, securizată și gata pentru utilizare de către echipă.

---

## 4. Recomandare privind prioritatea

Pentru o lansare eficientă, recomand următoarea ordine de implementare:

1. Etapa 0 – arhitectură și permisiuni
2. Etapa 1 – public website + lead generation
3. Etapa 2 – vânzări + oferte
4. Etapa 3 – tehnic + instalări
5. Etapa 4 – administrativ
6. Etapa 5 – integrare și automatizări
7. Etapa 6 – QA și lansare

Această ordine permite ca produsul să producă valoare rapid, fără să blocheze restul dezvoltării.

---

## 5. Checklist de progres general

- [x] Etapa 0: foundation
- [x] Etapa 1: public site
- [x] Etapa 2: vânzări
- [x] Etapa 3: tehnic
- [x] Etapa 4: administrativ
- [ ] Etapa 5: integrări (finalizata, mai putin plata online - amanata deliberat pana la nevoie reala)
- [~] Etapa 6: testare și lansare (finalizata local; lansarea efectiva asteapta detalii de hosting)

---

## 6. Concluzie

Planul actual este bine structurat, dar trebuie transformat într-un roadmap de execuție cu etape verificabile. Viziunea arhitecturală este corectă; diferența majoră între un plan bun și unul slab este capacitatea de a urmări progresul în mod clar, prin taskuri mici și rezultate măsurabile.

Următorul pas este să se pornească cu Etapa 0 și să se completeze checklist-ul câte puțin, la finalul fiecărei zile sau săptămâni.
