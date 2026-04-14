# Formularios Admin (Dinámico)

Plugin de WordPress que crea el menú "Formularios" en el panel de administración y organiza automáticamente los Custom Post Types (CPT) que tengan asignada la taxonomía `formularios`.

## Características

- **Menú centralizado**: Agrupa todos los CPT con taxonomía `formularios` bajo un solo menú en el admin.
- **Dashboard visual**: Página principal con tarjetas informativas por cada tipo de formulario (slug, rewrite, conteo de publicaciones).
- **Submenús automáticos**: Cada CPT detectado se añade como submenú para acceso rápido.
- **Sin duplicados**: Oculta las entradas originales del menú de los CPT reagrupados.
- **Detección dinámica**: Funciona con CPT creados por CPT UI, plugins o código del tema.
- **Ícono propio**: Usa el ícono `dashicons-feedback` en el menú lateral de WordPress.

## Requisitos

- WordPress 6.0+
- PHP 7.4+
- CPTs con la taxonomía `formularios` registrada

## Instalación

1. Descarga el archivo `formularios-admin.zip` de la última release.
2. En el admin de WordPress, ve a **Plugins → Añadir nuevo → Subir plugin**.
3. Selecciona el ZIP y haz clic en **Instalar ahora**.
4. Activa el plugin.

### Alternativa (manual)

```bash
cd wp-content/plugins/
git clone https://github.com/pedrozopayares/formularios-admin.git
```

## Uso

El plugin funciona de forma automática. Solo necesitas asignar la taxonomía `formularios` a tus CPT personalizados (desde CPT UI o por código) y el plugin los agrupará bajo el menú "Formularios".
