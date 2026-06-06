[◄◄ Insights](07-insights.md)
[▲ Tabla de contenido](00-tabla-de-contenido.md)

# 🔄 Handover Protocol — Messenger

Transferí el control de una conversación entre aplicaciones, bots y el inbox de Facebook. Ideal para flujos bot → humano.

## 📋 Requisitos

- **Permiso**: `pages_messaging`
- La app destino debe estar configurada como Secondary Receiver en Meta Dashboard

## 🚀 Uso

```php
use ScriptDevelop\InstagramApiManager\Facades\Facebook;

$handover = Facebook::handover()
    ->withPageAccessToken('EAAxxx...');
```

### Pasar control a otra app

```php
// Pasar conversación al inbox de Facebook (humano)
$handover->passThreadControl(
    recipientId: 'PSID',
    targetAppId: '263902037430900'  // Page Inbox
);
```

### Tomar control

```php
$handover->takeThreadControl('PSID');
```

### Ver apps disponibles

```php
$receivers = $handover->getSecondaryReceivers();
// [{id: '263902...', name: 'Page Inbox'}, ...]
```

## 📊 Tabla de Métodos

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `passThreadControl` | Pasar control a otra app | `bool` |
| `takeThreadControl` | Tomar control de conversación | `bool` |
| `getSecondaryReceivers` | Listar apps disponibles | `?array` |

## 🔄 Flujo Típico

```
Usuario escribe → Bot responde → Usuario pide humano
                                        │
                          $handover->passThreadControl($psid, 'page_inbox')
                                        │
                          Humano responde desde Facebook Inbox
```

---

[◄◄ Insights](07-insights.md)
