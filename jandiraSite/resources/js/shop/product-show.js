document.addEventListener('DOMContentLoaded', () => {
    initGallery();
    initVariants();
});

function initGallery() {
    const mainImage = document.getElementById('product-main-image');
    const thumbnails = document.querySelectorAll('.store-product-thumbnail[data-image-url]');

    if (!mainImage || !thumbnails.length) {
        return;
    }

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            const imageUrl = thumbnail.dataset.imageUrl;
            const imageAlt = thumbnail.dataset.imageAlt;

            if (!imageUrl) {
                return;
            }

            mainImage.src = imageUrl;

            if (imageAlt) {
                mainImage.alt = imageAlt;
            }

            thumbnails.forEach((item) => {
                item.classList.remove('active');
                item.setAttribute('aria-pressed', 'false');
            });

            thumbnail.classList.add('active');
            thumbnail.setAttribute('aria-pressed', 'true');
        });
    });
}

function initVariants() {
    const variantButtons = document.querySelectorAll('.store-variant-option');

    if (!variantButtons.length) {
        return;
    }

    const priceElement = document.getElementById('product-price');
    const priceLabel = document.getElementById('product-price-label');
    const stockElement = document.getElementById('product-stock');

    const cartVariantInput = document.getElementById('cart-variant-id');
    const alertVariantInput = document.getElementById('alert-variant-id');
    const stockAlertBox = document.getElementById('stock-alert-box');
    const interestButton = document.getElementById('interest-button');


    variantButtons.forEach((button) => {
        button.addEventListener('click', () => {

            // Remove a seleção atual
            variantButtons.forEach((item) => {
                item.classList.remove('active');
            });

            // Marca a nova variação
            button.classList.add('active');


            // Dados da variação selecionada
            const variantId = button.dataset.variantId;
            const price = button.dataset.priceFormatted;
            const stock = Number(button.dataset.stock);


            // Atualiza a variação que será enviada ao carrinho e ao alerta de estoque
            if (cartVariantInput && variantId) {
                cartVariantInput.value = variantId;
            }

            if (alertVariantInput && variantId) {
                alertVariantInput.value = variantId;
            }

            if (stockAlertBox) {
                stockAlertBox.classList.toggle('hidden', stock > 0);
            }


            // Atualiza o preço exibido
            if (priceElement && price) {
                priceElement.textContent = price;
            }

            if (priceLabel) {
                priceLabel.textContent = 'Preço';
            }


            // Atualiza estoque e estado do botão
            if (stockElement) {

                if (stock > 0) {

                    stockElement.textContent =
                        `Disponível — ${stock} unidade${stock > 1 ? 's' : ''}`;

                    stockElement.classList.remove(
                        'store-stock-unavailable'
                    );

                    stockElement.classList.add(
                        'store-stock-available'
                    );

                    if (interestButton) {
                        interestButton.disabled = false;
                        interestButton.classList.remove('disabled');
                    }

                } else {

                    stockElement.textContent = 'Indisponível';

                    stockElement.classList.remove(
                        'store-stock-available'
                    );

                    stockElement.classList.add(
                        'store-stock-unavailable'
                    );

                    if (interestButton) {
                        interestButton.disabled = true;
                        interestButton.classList.add('disabled');
                    }
                }
            }

        });
    });
}