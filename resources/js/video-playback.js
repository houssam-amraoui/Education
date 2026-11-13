const frame = document.querySelector("iframe[data-playback-url]");

if (frame) {
    let reported = false;

    window.onYouTubeIframeAPIReady = () => {
        new window.YT.Player(frame, {
            events: {
                async onStateChange(event) {
                    if (reported || event.data !== window.YT.PlayerState.PLAYING) {
                        return;
                    }

                    reported = true;
                    try {
                        const response = await fetch(frame.dataset.playbackUrl, {
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": frame.dataset.playbackToken },
                            keepalive: true,
                        });
                        reported = response.ok;
                    } catch {
                        reported = false;
                    }
                },
            },
        });
    };

    const script = document.createElement("script");
    script.src = "https://www.youtube.com/iframe_api";
    document.head.append(script);
}
