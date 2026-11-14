const frames = document.querySelectorAll("iframe[data-playback-url]");

if (frames.length) {
    window.onYouTubeIframeAPIReady = () => {
        frames.forEach((frame) => {
            let reported = false;

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
        });
    };

    const script = document.createElement("script");
    script.src = "https://www.youtube.com/iframe_api";
    document.head.append(script);
}
