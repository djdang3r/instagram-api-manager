# Instagram API Manager for Laravel

<p align="center">
<img src="https://raw.githubusercontent.com/ScriptDevelop/instagram-api-manager/main/art/banner.png" alt="Instagram API Manager Banner" width="800">
</p>

<p align="center">
<a href="https://packagist.org/packages/scriptdevelop/instagram-api-manager"><img src="https://img.shields.io/packagist/v/scriptdevelop/instagram-api-manager.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://php.net/"><img src="https://img.shields.io/badge/PHP-8.2%2B-8892BF.svg?style=flat-square" alt="PHP Version"></a>
<a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-12%2B-FF2D20.svg?style=flat-square" alt="Laravel Version"></a>
<a href="https://packagist.org/packages/scriptdevelop/instagram-api-manager"><img src="https://img.shields.io/packagist/dt/scriptdevelop/instagram-api-manager" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/scriptdevelop/instagram-api-manager"><img src="https://img.shields.io/packagist/l/scriptdevelop/instagram-api-manager" alt="License"></a>
</p>

---

### [🇪🇸 Español](#-español) | [🇺🇸 English](#-english)

---

## 🇪🇸 Español

### 📝 Descripción Detallada
**Instagram API Manager** es una solución empresarial de código abierto para Laravel 12+, desarrollada para cerrar la brecha entre las complejas especificaciones de la **Graph API de Instagram** y las aplicaciones modernas. Este paquete no es solo un envoltorio (wrapper) de API; es un ecosistema completo que permite a los desarrolladores gestionar interacciones de Instagram Business a escala.

Desde la automatización de respuestas en Direct Message (DM) hasta la gestión de perfiles comerciales y la implementación de herramientas de crecimiento (Growth Tools), este paquete proporciona una interfaz fluida basada en Facades, Modelos Eloquent y Servicios modulares, permitiéndote centrarte en la lógica de tu negocio mientras nosotros nos encargamos de la complejidad de Meta.

> [!CAUTION]
> **Aviso de marca y protección de marca (METAS):**
> Este es un paquete de **CÓDIGO ABIERTO NO OFICIAL**. No está afiliado, asociado, autorizado, respaldado ni conectado oficialmente con Meta Platforms, Inc., Instagram, ni ninguna de sus subsidiarias o afiliadas. Las marcas comerciales "Instagram", "Meta" y "Facebook", así como los nombres, marcas, emblemas e imágenes relacionados, son marcas registradas de sus respectivos propietarios. El uso de este paquete debe cumplir plenamente con las [Políticas de la Plataforma de Meta](https://developers.facebook.com/terms/).

> [!IMPORTANT]
> **Estado del Proyecto:** Este paquete se encuentra actualmente en **Fase ALFA**. Está en desarrollo activo y aún no ha alcanzado la versión Beta ni una versión estable. **No se recomienda su uso en entornos de producción.** Puedes probarlo y usarlo bajo tu propia responsabilidad.

### 📖 Documentación
| Sección | Descripción |
| :--- | :--- |
| [1. 🚀 Instalación](documentation/es/01-instalacion.md) | Guía paso a paso para instalar y preparar el entorno Laravel. |
| [2. 🧩 Configuración](documentation/es/02-configuracion.md) | Configura tus credenciales de Meta y personaliza el comportamiento del paquete. |
| | **📸 Instagram** |
| [3. 👤 Cuentas](documentation/es/instagram/03-cuentas.md) | Gestión avanzada de perfiles, permisos OAuth e identificación de cuentas. |
| [4. 💬 Mensajería](documentation/es/instagram/04-mensajes.md) | Envío masivo y filtrado de texto, multimedia, stickers y plantillas interactivas. |
| [5. 🛠️ Automatización](documentation/es/instagram/05-menu-persistente.md) | Configuración de la experiencia de chat: Menús persistentes e Ice Breakers. |
| [6. 🔗 Growth Tools](documentation/es/instagram/06-enlaces.md) | Generación dinámica de enlaces ig.me y códigos QR oficiales de Meta. |
| [7. 📡 Webhooks](documentation/es/instagram/07-webhooks.md) | Arquitectura de eventos para recibir y procesar mensajes en tiempo real. |
| | **💬 Facebook Messenger** |
| [🔗 Autenticación](documentation/es/messenger/01-autenticacion.md) | Conexión OAuth de páginas de Facebook, permisos y registro manual. |
| [💬 Mensajería](documentation/es/messenger/02-mensajes.md) | Envío y recepción de mensajes: texto, multimedia, templates, reacciones. |
| [📡 Webhooks](documentation/es/messenger/03-webhooks.md) | Configuración del webhook de Messenger y formato de payloads. |
| [📡 Eventos](documentation/es/messenger/04-eventos.md) | Broadcasting con Laravel Reverb y suscripción frontend. |

### ✨ Características Principales
- ✅ **Arquitectura Escalable**: Diseñado para soportar desde una sola cuenta hasta cientos de perfiles comerciales simultáneamente.
- ✅ **Persistencia Eloquent**: Guarda automáticamente conversaciones, contactos y mensajes para facilitar el análisis histórico.
- ✅ **Gestión de Tokens**: Automatización del flujo de intercambio de tokens de corta duración por tokens de larga duración.
- ✅ **Mensajería Enriquecida**: Soporte completo para Quick Replies, Generic Templates y Reacciones de Instagram.
- ✅ **Seguridad Nativa**: Verificación de firmas X-Hub-Signature en webhooks para garantizar que los datos provienen de Meta.

---

## 🇺🇸 English

### 📝 Detailed Description
**Instagram API Manager** is an enterprise-grade open-source solution for Laravel 12+, built to bridge the gap between complex **Instagram Graph API** specifications and modern applications. This package is not just an API wrapper; it is a full ecosystem that allows developers to manage Instagram Business interactions at scale.

From automating Direct Message (DM) responses to managing commercial profiles and implementing Growth Tools, this package provides a fluid interface based on Facades, Eloquent Models, and modular Services, allowing you to focus on your business logic while we handle the complexity of Meta.

> [!CAUTION]
> **Trademark Notice & Protection (METAS):**
> This is an **UNOFFICIAL OPEN SOURCE** package. It is not affiliated, associated, authorized, endorsed by, or in any way officially connected with Meta Platforms, Inc., Instagram, or any of its subsidiaries or affiliates. The trademarks "Instagram", "Meta", and "Facebook", as well as related names, marks, emblems, and images, are registered trademarks of their respective owners. Use of this package must fully comply with [Meta Platform Policies](https://developers.facebook.com/terms/).

> [!IMPORTANT]
> **Project Status:** This package is currently in **ALPHA Phase**. It is under active development and has not yet reached Beta or a stable version. **Use in production environments is not recommended.** You may test and use it at your own risk.

### 📖 Documentation
| Section | Description |
| :--- | :--- |
| [1. 🚀 Installation](documentation/en/01-installation.md) | Step-by-step guide to install and prepare the Laravel environment. |
| [2. 🧩 Configuration](documentation/en/02-configuration.md) | Configure your Meta credentials and customize package behavior. |
| | **📸 Instagram** |
| [3. 👤 Accounts](documentation/en/instagram/03-accounts.md) | Advanced profile management, OAuth permissions, and account identification. |
| [4. 💬 Messaging](documentation/en/instagram/04-messages.md) | Batch sending and filtering of text, media, stickers, and interactive templates. |
| [5. 🛠️ Automation](documentation/en/instagram/05-persistent-menu.md) | Chat experience setup: Persistent menus and Ice Breakers. |
| [6. 🔗 Growth Tools](documentation/en/instagram/06-links.md) | Dynamic ig.me link generation and official Meta QR codes. |
| [7. 📡 Webhooks](documentation/en/instagram/07-webhooks.md) | Event architecture for receiving and processing messages in real-time. |
| | **💬 Facebook Messenger** |
| [🔗 Authentication](documentation/en/messenger/01-authentication.md) | Facebook Page OAuth connection, permissions, and manual registration. |
| [💬 Messaging](documentation/en/messenger/02-messages.md) | Send and receive messages: text, media, templates, reactions. |
| [📡 Webhooks](documentation/en/messenger/03-webhooks.md) | Messenger webhook setup and payload format. |
| [📡 Events](documentation/en/messenger/04-events.md) | Broadcasting with Laravel Reverb and frontend subscription. |

### ✨ Key Features
- ✅ **Scalable Architecture**: Designed to support from a single account to hundreds of business profiles simultaneously.
- ✅ **Eloquent Persistence**: Automatically saves conversations, contacts, and messages for easy historical analysis.
- ✅ **Token Management**: Automates the flow of exchanging short-lived tokens for long-lived tokens.
- ✅ **Rich Messaging**: Full support for Instagram Quick Replies, Generic Templates, and Reactions.
- ✅ **Native Security**: X-Hub-Signature verification on webhooks to guarantee data comes from Meta.

---

## 🚀 Instalación Rápida / Quick Start

```bash
composer require scriptdevelop/instagram-api-manager
```

```php
use ScriptDevelop\InstagramApiManager\Facades\Instagram;

// Enviar un mensaje / Send a message
Instagram::message()
    ->withAccessToken($token)
    ->withInstagramUserId($igId)
    ->sendTextMessage('RECIPIENT_ID', 'Welcome to Instagram API Manager!');
```

---

## 🤝 Contribuir / Contributing

¡Las contribuciones son lo que hacen a la comunidad open source un lugar increíble para aprender, inspirar y crear! Si deseas mejorar este paquete:

1. Haz un **Fork** del proyecto.
2. Crea una **Rama** para tu mejora (`git checkout -b feature/AmazingFeature`).
3. Haz **Commit** de tus cambios (`git commit -m 'Add some AmazingFeature'`).
4. Haz **Push** a la rama (`git push origin feature/AmazingFeature`).
5. Abre un **Pull Request**.

---

## 📄 Licencia / License

Este proyecto está bajo la Licencia MIT. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---

<p align="center">
Desarrollado con ❤️ por <a href="https://github.com/djdang3r">Wilfredo Perilla</a> de <b>ScriptDevelop</b>.
</p>
