import { r as React, j as jsxRuntime } from "./react-vendor-BoP3VcUd.js";

const API_URL = "https://api.frugaldomain.site/api/desktop-apps";
const PLATFORMS = ["all", "windows", "linux", "macos", "other"];
const jsx = (...args) => jsxRuntime.jsx(...args);

function DesktopDownloadsPage() {
  const [software, setSoftware] = React.useState([]);
  const [platform, setPlatform] = React.useState("all");
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState("");
  const isArabic = window.location.pathname.startsWith("/ar");

  React.useEffect(() => {
    let active = true;

    fetch(API_URL)
      .then((response) => {
        if (!response.ok) {
          throw new Error(isArabic ? "تعذر تحميل التطبيقات." : "Could not load downloads.");
        }

        return response.json();
      })
      .then((payload) => {
        if (active) setSoftware(payload.data);
      })
      .catch((cause) => {
        if (active) setError(cause.message);
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [isArabic]);

  const visibleSoftware = software.filter(
    (item) => platform === "all" || item.platform === platform,
  );
  const text = isArabic
    ? {
        title: "تطبيقات سطح المكتب",
        description: "حمّل التطبيقات التي طورناها لنظامك، واقرأ تفاصيل التشغيل قبل التثبيت.",
        all: "كل الأنظمة",
        windows: "ويندوز",
        linux: "لينكس",
        macos: "macOS",
        other: "أنظمة أخرى",
        download: "تحميل التطبيق",
        environment: "بيئة التشغيل",
        openSource: "مفتوح المصدر",
        closedSource: "مغلق المصدر",
        readme: "اقرأني",
        version: "الإصدار",
        empty: "لا توجد تطبيقات متاحة لهذا النظام حالياً.",
        loading: "جارٍ تحميل التطبيقات...",
        platform: (item) => item.platform_name || item.platform,
      }
    : {
        title: "Desktop applications",
        description: "Download our desktop software and check its requirements before installing.",
        all: "All platforms",
        windows: "Windows",
        linux: "Linux",
        macos: "macOS",
        other: "Other",
        download: "Download application",
        environment: "Runtime environment",
        openSource: "Open source",
        closedSource: "Closed source",
        readme: "README",
        version: "Version",
        empty: "No applications are available for this platform yet.",
        loading: "Loading downloads...",
        platform: (item) => item.platform_name || item.platform,
      };

  return jsx("main", {
    dir: isArabic ? "rtl" : "ltr",
    className: "mx-auto w-full max-w-6xl px-4 py-10 sm:py-14",
    children: [
      jsx("header", {
        className: "max-w-3xl",
        children: [
          jsx("p", {
            className: "text-xs font-bold uppercase tracking-[0.2em] text-fg-accent",
            children: isArabic ? "التنزيلات" : "Downloads",
          }),
          jsx("h1", {
            className: "mt-3 text-3xl font-extrabold tracking-tight text-primary-text sm:text-4xl",
            children: text.title,
          }),
          jsx("p", {
            className: "mt-3 text-sm leading-7 text-secondary-text sm:text-base",
            children: text.description,
          }),
        ],
      }),
      jsx("nav", {
        "aria-label": isArabic ? "تصفية حسب نظام التشغيل" : "Filter by operating system",
        className: "mt-8 flex flex-wrap gap-2",
        children: PLATFORMS.map((value) =>
          jsx(
            "button",
            {
              type: "button",
              onClick: () => setPlatform(value),
              "aria-pressed": platform === value,
              className:
                platform === value
                  ? "rounded-full bg-fg-accent px-4 py-2 text-sm font-semibold text-white"
                  : "rounded-full border border-main-border bg-blocks-color px-4 py-2 text-sm font-semibold text-secondary-text hover:text-primary-text",
              children: text[value],
            },
            value,
          ),
        ),
      }),
      loading
        ? jsx("p", {
            className: "mt-10 rounded-xl border border-main-border bg-blocks-color p-6 text-sm text-secondary-text",
            children: text.loading,
          })
        : error
          ? jsx("p", {
              role: "alert",
              className: "mt-10 rounded-xl border border-main-border bg-blocks-color p-6 text-sm text-error-text",
              children: error,
            })
          : visibleSoftware.length
            ? jsx("div", {
                className: "mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2",
                children: visibleSoftware.map((item) =>
                  jsx(
                    "article",
                    {
                      className: "flex flex-col rounded-2xl border border-main-border bg-blocks-color p-5 shadow-sm sm:p-6",
                      children: [
                        jsx("div", {
                          className: "flex flex-wrap items-start justify-between gap-3",
                          children: [
                            jsx("h2", {
                              className: "text-xl font-bold text-primary-text",
                              children: item.name,
                            }),
                            jsx("span", {
                              className: "rounded-full border border-main-border px-3 py-1 text-xs font-semibold text-secondary-text",
                              children: text.platform(item),
                            }),
                          ],
                        }),
                        item.version
                          ? jsx("p", {
                              className: "mt-2 text-sm text-secondary-text",
                              children: `${text.version}: ${item.version}`,
                            })
                          : null,
                        item.summary
                          ? jsx("p", {
                              className: "mt-4 text-sm leading-7 text-secondary-text",
                              children: item.summary,
                            })
                          : null,
                        jsx("p", {
                          className: "mt-4 text-sm text-secondary-text",
                          children: [
                            jsx("span", {
                              className: "font-semibold text-primary-text",
                              children: `${text.environment}: `,
                            }),
                            item.environment,
                          ],
                        }),
                        jsx("p", {
                          className: "mt-2 text-sm text-secondary-text",
                          children: item.is_open_source ? text.openSource : text.closedSource,
                        }),
                        jsx("a", {
                          href: item.download_url,
                          className: "mt-5 inline-flex w-fit items-center justify-center rounded-lg bg-fg-accent px-4 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90",
                          children: text.download,
                        }),
                        jsx("section", {
                          className: "mt-6 border-t border-main-border pt-5",
                          children: [
                            jsx("h3", {
                              className: "text-base font-bold text-primary-text",
                              children: text.readme,
                            }),
                            jsx("pre", {
                              dir: "auto",
                              className: "mt-3 whitespace-pre-wrap break-words font-sans text-sm leading-7 text-secondary-text",
                              children: item.readme,
                            }),
                          ],
                        }),
                      ],
                    },
                    item.slug,
                  ),
                ),
              })
            : jsx("p", {
                className: "mt-10 rounded-xl border border-main-border bg-blocks-color p-6 text-sm text-secondary-text",
                children: text.empty,
              }),
    ],
  });
}

export { DesktopDownloadsPage as default };
