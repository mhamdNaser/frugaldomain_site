from __future__ import annotations

import os
import shutil
import subprocess

import gradio as gr
import spaces
from gradio import Blocks
from gradio.routes import App as GradioApp

from app.main import app as conversion_api
from app.runner import tool_versions
from app.settings import settings


@spaces.GPU
def check_conversion_readiness() -> str:
    missing = [name for name, path in tool_versions().items() if path is None]
    if missing:
        return f"Not ready. Missing tools: {', '.join(missing)}."
    if not settings.api_key:
        return "Not ready. Configure the CONVERT_API_KEY Space secret."
    return "Ready. Document and audio conversions run with CPU tools."


with Blocks() as space_ui:
    gr.Markdown(
        """
        # FrugalDomain file converter

        This Space provides the conversion API used by FrugalDomain.
        Documents and audio are processed temporarily and are not kept after
        the response is sent.

        The website calls `/health`, `/convert/document`, and `/convert/audio`.
        """
    )
    readiness = gr.Textbox(label="Conversion service status", interactive=False)
    check_button = gr.Button("Check service readiness")
    check_button.click(
        fn=check_conversion_readiness,
        inputs=[],
        outputs=readiness,
    )

space_ui.queue()

app = GradioApp()
for middleware in conversion_api.user_middleware:
    app.add_middleware(middleware.cls, **middleware.options)
app.include_router(conversion_api.router)


if __name__ == "__main__":
    font_cache = shutil.which("fc-cache")
    if font_cache is None:
        raise RuntimeError("fontconfig is required to initialize the Arabic fonts")
    subprocess.run([font_cache, "-f"], check=True)
    space_ui.launch(
        server_name="0.0.0.0",
        server_port=int(os.environ.get("PORT", "7860")),
        ssr_mode=False,
        _app=app,
    )
