$(document).ready(() => {
    // Prepare URL params to get used (feature flags)
    const urlParams = new URLSearchParams(window.location.search);
    const openImagesInTab = urlParams.has('tab')

    // Attach click handlers to all figures in the content
    $('.content figure').each(function() {
        $(this).click(function() {
            if (openImagesInTab) {
                $(this).find('img').each(function() {
                    const imageUrl = $(this).attr('src')
                    window.open(imageUrl, '_blank')
                })
            } else {
                $(this).toggleClass('zoomed')
            }
        })
    })
})
