import {
    Card,
    Page,
    Layout,
    TextContainer,
    LegacyCard,
    Button,
    Text,
} from "@shopify/polaris";
import { TitleBar } from "@shopify/app-bridge-react";
import { useTranslation } from "react-i18next";

export default function Dashboard() {
    return (
        <Page
            backAction={{ content: "Settings", url: "#" }}
            title="Dashboard"
            primaryAction={<Button variant="primary">Save</Button>}
        >
            <LegacyCard title="Credit card" sectioned>
                <p>Credit card information</p>
            </LegacyCard>
        </Page>
    );
}
