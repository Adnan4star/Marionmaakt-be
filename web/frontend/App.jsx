import { BrowserRouter } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { NavigationMenu } from "@shopify/app-bridge-react";
import Routes from "./Routes";
import { AppProvider } from "@shopify/polaris";
import en from "@shopify/polaris/locales/en.json";

import "@shopify/polaris/build/esm/styles.css";
import {
    AppBridgeProvider,
    QueryProvider,
    PolarisProvider,
} from "./components";
export default function App() {
    const pages = import.meta.globEager(
        "./pages/**/!(*.test.[jt]sx)*.([jt]sx)"
    );

    return (
        <AppProvider>
            <PolarisProvider>
                <BrowserRouter>
                    <AppBridgeProvider>
                        <QueryProvider>
                            <NavigationMenu
                                navigationLinks={[
                                    {
                                        label: "Dashboard",
                                        destination: "/Dashboard",
                                    },
                                    {
                                        label: "Filters",
                                        destination: "/Filters",
                                    },
                                    {
                                        label: "BlogPost",
                                        destination: "/BlogPost",
                                    },

                                    {
                                        label: "Search",
                                        destination: "/Search",
                                    },
                                ]}
                            />
                            <Routes pages={pages} />
                        </QueryProvider>
                    </AppBridgeProvider>
                </BrowserRouter>
            </PolarisProvider>
        </AppProvider>
    );
}
