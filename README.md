# 🕵️‍♂️ Imposter Game

> *Blend in, figure out the secret word, or spot the liar before time runs out.*

A fast-paced, local multiplayer social deduction and word game designed for game nights without needing a designated game master or human moderator. Players pass the device or join to reveal their secret word—except for the designated "imposter," who must bluff their way through rounds by reading the room.

---

## 🚀 Tech Stack

* **Backend:** PHP / Laravel (Monolithic application structure)
* **Frontend:** Vue 3 with Inertia.js (Single-page app experience)
* **Styling:** Tailwind CSS (Minimalist, responsive, and mobile-friendly grid layouts)
* **Storage:** Local static dataset & seeders (`WordPairSeeder`) — no database setup required

---

## ✨ Core Features

* **No Moderator Required:** Automated round orchestration handles roles, secret words, and imposter assignments so everyone gets to play.
* **Zero Database Overhead:** Word pairs and categories are stored and served directly from local application memory, making setup instant.
* **Real-Time Social Deduction:** Dynamic gameplay mechanics forcing players to weigh every clue.
* **Seamless SPA Interface:** Powered by Inertia.js and Vue 3 for fast, reactive client-side transitions.
* **Responsive Layout:** Optimized to run smoothly across desktop, tablet, and mobile browsers.

---

## 🛠️ Local Setup

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/wannaddd/imposter-game.git](https://github.com/wannaddd/imposter-game.git)
   cd imposter-game
